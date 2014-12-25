<?php

namespace sonicgd\swiftmailer\mandrill;

/**
 * Class Swift_Message_Mandrill
 * @package sonicgd\swiftmailer\mandrill
 */
class Swift_Message_Mandrill extends \Swift_Message
{
    private $tags = [];

    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    public function getTags()
    {
        return $this->tags;
    }

}