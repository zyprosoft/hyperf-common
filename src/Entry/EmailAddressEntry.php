<?php


namespace ZYProSoft\Entry;


class EmailAddressEntry
{
    public string $address;

    public string $name;

    public function __construct(string $address, string $name)
    {
        $this->address = $address;
        $this->name = $name;
    }
}