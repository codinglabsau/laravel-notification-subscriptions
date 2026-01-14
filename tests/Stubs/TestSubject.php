<?php

namespace Codinglabs\NotificationSubscriptions\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TestSubject extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory()
    {
        return TestSubjectFactory::new();
    }
}
