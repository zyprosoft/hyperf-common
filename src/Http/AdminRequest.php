<?php


namespace ZYProSoft\Http;


class AdminRequest extends AuthedRequest
{
    public function rules()
    {
        return [];
    }

    protected function authorize()
    {
        return $this->isAdmin();
    }
}