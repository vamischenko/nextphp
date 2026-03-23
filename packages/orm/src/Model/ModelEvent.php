<?php

declare(strict_types=1);

namespace Nextphp\Orm\Model;

enum ModelEvent: string
{
    case Creating  = 'creating';
    case Created   = 'created';
    case Updating  = 'updating';
    case Updated   = 'updated';
    case Saving    = 'saving';
    case Saved     = 'saved';
    case Deleting  = 'deleting';
    case Deleted   = 'deleted';
    case Restoring = 'restoring';
    case Restored  = 'restored';
}
