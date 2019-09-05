<?php
namespace Rebet\Tests\Mock;

use Rebet\Database\DataModel\Entity;

class Article extends Entity
{
    public $article_id;
    public $user_id;
    public $subject;
    public $body;
    public $created_at;
    public $updated_at;
}
