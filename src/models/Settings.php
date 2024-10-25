<?php
namespace kraftwerkdesign\pdfthumbnailer\models;

use craft\base\Model;

class Settings extends Model
{
    public $thumbnailFolderId;

    public function rules(): array
    {
        return [
            ['thumbnailFolderId', 'required'],
            ['thumbnailFolderId', 'integer'],
        ];
    }
}