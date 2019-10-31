<?php
namespace Rebet\Tests\Mock;

use Rebet\Database\Annotation\PhpType;
use Rebet\Database\DataModel\Entity;
use Rebet\DateTime\DateTime;

class Article extends Entity
{
    public $article_id;
    public $user_id;
    public $subject;
    public $body;
    /**
     * @PhpType(DateTime::class)
     */
    public $created_at;
    /**
     * @PhpType(DateTime::class)
     */
    public $updated_at;
}
