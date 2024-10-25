<?php
namespace jhanshew\craftpdfthumbnailer\models;

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