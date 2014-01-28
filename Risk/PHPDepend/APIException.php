<?php
/**
 * @author Christine Gerpheide <cgerpheide@box.com>
 * @since 9/9/13
 */
namespace Risk\PHPDepend;

class APIException extends Exception {

    protected $contents;

    public function __construct($message, $contents)
    {
        parent::__construct($message);
        $this->contents = $contents;
    }

    public function __toString()
    {
        return get_class() . ': ' . $this->message . "\n\n". $this->contents;
    }

}