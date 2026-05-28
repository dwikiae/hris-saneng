<?php

namespace App\Domain\Storage;

enum StorageVisibility: string
{
    case Private = 'private';
    case Public = 'public';
}
