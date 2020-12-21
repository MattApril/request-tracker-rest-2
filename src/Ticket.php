<?php

declare(strict_types=1);

namespace MattApril\RequestTracker;

/**
 * Class Ticket
 * Makes Ticket based API calls
 *
 * @author Matthew April
 */
class Ticket extends ApiResource
{

    /**
     * Load a ticket by ID
     *
     * @param int $ticketId
     * @return mixed
     * @throws \Exception
     */
    public function get( int $ticketId ) {
        $response = $this->sendRequest('GET', "ticket/{$ticketId}");

        $result = json_decode($response->getBody()->getContents());

        # make sure response was valid json..
        if( json_last_error() != JSON_ERROR_NONE ) {
            throw new \Exception('Failed to decode json response from RT REST2 API.');
        }

        return $result;
    }

    /**
     * Create a new ticket
     *
     * @param array $properties
     *
     * @return mixed
     * @throws \Exception
     */
    public function create( array $properties ) {
        // TODO: Creation of custom fields was not working.. removed for now
        // you must send an update request after creating if you want to add CustomFields.
        /*
        if( count($customFields) > 0 ) {
            $properties['CustomFields'] = $customFields;
        }*/

        $json = json_encode($properties);

        $response = $this->sendRequest('POST', "ticket", $json, [
            'Content-Type' => 'application/json'
        ]);

        $result = json_decode($response->getBody()->getContents());
        # make sure response was valid json..
        if( json_last_error() != JSON_ERROR_NONE ) {
            throw new \Exception('Failed to decode json response from RT REST2 API.');
        }

        return $result;
    }

    /**
     * Update and existing ticket
     *
     * @param int $ticketId
     * @param array $properties
     * @param array $customFields
     *
     * @return array
     * @throws \Exception
     */
    public function update( int $ticketId, array $properties, array $customFields=[] ) {
        if( count($customFields) > 0 ) {
            $properties['CustomFields'] = $customFields;
        }

        $json = json_encode($properties);
        $response = $this->sendRequest('PUT', "ticket/{$ticketId}", $json, [
            'Content-Type' => 'application/json'
        ]);

        $result = json_decode($response->getBody()->getContents());
        # make sure response was valid json..
        if( json_last_error() != JSON_ERROR_NONE ) {
            throw new \Exception('Failed to decode json response from RT REST2 API.');
        }

        return $result;
    }

    /**
     * @param int $ticketId
     * @param string $html
     *
     * @return int
     */
    public function addHtmlComment( int $ticketId, string $html ) {
        return $this->addComment($ticketId, $html, 'text/html');
    }

    /**
     * @param int $ticketId
     * @param string $text
     *
     * @return int
     */
    public function addTextComment( int $ticketId, string $text ) {
        return $this->addComment($ticketId, $text, 'text/plain');
    }

    /**
     * @param int $ticketId
     * @param string $dataText
     * @TODO: incomplete
     */
    public function searchTransactions( int $ticketId, string $dataText ) {
        $params = [
            'Type' => 'Comment',
            'ObjectType' => 'RT::Ticket',
            'ObjectId' => $ticketId,
        ];

        $body = [
            array(
                'field' => 'Data',
                'operator' => 'LIKE',
                'value' => $dataText
            )
        ];

        foreach( $params as $field => $value ) {
            $body[] = [
                'field' => $field,
                'value' => $value
            ];
        }

        $response = $this->sendRequest( 'POST', 'transactions', json_encode($body) );
        $result = json_decode($response->getBody()->getContents());
        // TODO..
    }

    /**
     * @param int $ticketId
     * @param string $comment
     * @param string $mimeType
     *
     * @return int transaction id of comment
     * @throws \Exception
     */
    private function addComment( int $ticketId, string $comment, string $mimeType='text/plain' ): int {
        $response = $this->sendRequest('POST', "ticket/{$ticketId}/comment", $comment, [
            'Content-Type' => $mimeType
        ]);

        # get the url pointing to the newly created comment
        $commentResourceHeader = $response->getHeader('Location');
        if( count($commentResourceHeader) !== 1 ) {
            throw new \Exception('Unexpected response from RT API: missing \'Location\' header.');
        }
        $commentResource = $commentResourceHeader[0];

        # parse out the transaction id
        $expectedUrl = '/REST/2.0/transaction/';
        $urlBase = strpos($commentResource, $expectedUrl);
        if( $urlBase === false ) {
            throw new \Exception('Unexpected response from RT API: unexpected value in \'Location\' header.');
        }
        $transactionId = substr($commentResource, $urlBase + strlen($expectedUrl) );

        return (int)$transactionId;
    }
}