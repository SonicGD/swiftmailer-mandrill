<?php

namespace sonicgd\swiftmailer\mandrill;

use Exception;
use Mandrill;
use Mandrill_Error;
use Swift_Attachment;
use Swift_Events_EventListener;
use Swift_Mime_Message;
use Swift_Transport;

/**
 * Class Swift_Transport_Mandrill
 */
class Swift_Transport_Mandrill implements Swift_Transport
{

    /** Connection status */
    protected $_started = false;

    /** Your api key */
    private $_apiKey;

    /**
     * @var Mandrill $mandrill
     */
    private $mandrill;

    public function __construct($apiKey)
    {
        $this->_apiKey = $apiKey;
    }

    /**
     * Test if this Transport mechanism has started.
     *
     * @return bool
     */
    public function isStarted()
    {
        return $this->_started;
    }

    /**
     * Start this Transport mechanism.
     */
    public function start()
    {
        $this->mandrill = new Mandrill($this->_apiKey);
    }

    /**
     * Stop this Transport mechanism.
     */
    public function stop()
    {
        $this->mandrill = null;
    }

    /**
     * Send the given Message.
     *
     * Recipient/sender data will be retrieved from the Message API.
     * The return value is the number of recipients who were accepted for delivery.
     *
     * @param Swift_Mime_Message|Swift_Message_Mandrill $message
     * @param string[]                                  $failedRecipients An array of failures by-reference
     *
     * @return int
     * @throws Exception
     * @throws Mandrill_Error
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        try {
            $from = $message->getFrom();
            $to = [];
            foreach ($message->getTo() as $address => $name) {
                $to[] = [
                    'email' => $address,
                    'name'  => $name,
                    'type'  => 'to'
                ];
            }
            $attachments = [];
            $childrens = $message->getChildren();
            $html = "";
            $txt = "";
            foreach ($childrens as $children) {
                if ($children instanceof Swift_Attachment) {
                    /**
                     * @var Swift_Attachment $children
                     */
                    $attachments[] = [
                        'type'    => $children->getContentType(),
                        'name'    => $children->getFilename(),
                        'content' => base64_encode($children->getBody())
                    ];
                } elseif ($message->getBody() == null) {
                    if ($children->getContentType() == 'text/html') {
                        $html .= $children->getBody();
                    } else {
                        $txt .= $children->getBody();
                    }
                } else {
                    if ($message->getContentType() == 'text/html') {
                        $html .= $message->getBody();
                    } else {
                        $txt .= $message->getBody();
                    }
                }
            }
            $mail = array(
                'html'        => $html,
                'txt'         => $txt,
                'subject'     => $message->getSubject(),
                'from_email'  => array_keys($from)[0],
                'from_name'   => reset($from),
                'to'          => $to,
                'headers'     => array('Reply-To' => $message->getReplyTo()),
                'attachments' => $attachments,
                'tags'        => $message->getTags(),
            );
            $async = false;
            $ip_pool = 'Main Pool';

            $result = $this->mandrill->messages->send($mail, $async, $ip_pool);
            foreach ($result as $recepient) {
                if (!in_array($recepient['status'], ['queued', 'sent'])) {
                    throw new Exception('Mail to ' . $recepient['email'] . ' wasn\'t send');
                }
            }
            return true;
        } catch (Mandrill_Error $e) {
            // Mandrill errors are thrown as exceptions
            echo 'A mandrill error occurred: ' . get_class($e) . ' - ' . $e->getMessage();
            // A mandrill error occurred: Mandrill_Unknown_Subaccount - No subaccount exists with the id 'customer-123'
            throw $e;
        }
    }

    /**
     * Register a plugin in the Transport.
     *
     * @param Swift_Events_EventListener $plugin
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        return;
    }
}