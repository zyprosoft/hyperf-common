<?php


namespace ZYProSoft\Entry;


class EmailAttachmentEntry
{
    public string $path;

    public string $name = '';

    public function __construct(string $path, string $name)
    {
        $this->path = $path;
        $this->name = $name;
    }
}