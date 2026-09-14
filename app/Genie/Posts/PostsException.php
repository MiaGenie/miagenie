<?php

namespace App\Genie\Posts;

use RuntimeException;

/**
 * A reason a post cannot be produced or redone right now, named by the lang key the page shows.
 */
class PostsException extends RuntimeException
{
    public function __construct(public readonly string $key)
    {
        parent::__construct((string) __('genie.'.$key));
    }

    public static function ideaNotApproved(): self
    {
        return new self('post_idea_not_approved');
    }

    public static function alreadyExists(): self
    {
        return new self('post_already_exists');
    }

    public static function noRule(): self
    {
        return new self('posts_no_rule');
    }

    public static function noGeneratorForFormat(): self
    {
        return new self('posts_no_generator_for_format');
    }

    public static function noReviseTextStep(): self
    {
        return new self('post_no_revise_text_step');
    }

    public static function noImageFixStep(): self
    {
        return new self('post_no_image_fix_step');
    }

    public static function noImageRethinkStep(): self
    {
        return new self('post_no_image_rethink_step');
    }

    public static function busy(): self
    {
        return new self('post_busy');
    }

    public static function notFailed(): self
    {
        return new self('post_not_failed');
    }

    public static function noImage(): self
    {
        return new self('post_image_missing');
    }
}
