<?php
namespace PreCommit\Filter;

use PreCommit\Message;

/**
 * Class ShortCommitMsg filter
 *
 * @package PreCommit\Filter
 */
class Explode implements Message\InterfaceFilter
{
    /**
     * Filter short commit message
     *
     * @param \PreCommit\Message $message
     * @return string
     */
    public function filter(Message $message)
    {
        $message->body = trim($message->body);
        list($message->head, $message->userBody) = $this->_explodeMessage($message->body);
        return $message;
    }

    /**
     * Explode message
     *
     * @param string $inputMessage
     * @return array
     */
    protected function _explodeMessage($inputMessage)
    {
        $arr      = explode("\n", $inputMessage);
        $head     = array_shift($arr);
        $userBody = trim(str_replace($head, '', $inputMessage));
        return array($head, $userBody);
    }
}
