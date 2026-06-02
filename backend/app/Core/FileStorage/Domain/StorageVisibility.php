<?php

namespace App\Core\FileStorage\Domain;

enum StorageVisibility: string
{
    case Private = 'private';
    case Public = 'public';
}
