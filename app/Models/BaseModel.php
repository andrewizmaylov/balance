<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{
    public static function getTableName(): string
    {
        return (new static())->getTable();
    }
}
