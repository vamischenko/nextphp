<?php

declare(strict_types=1);

namespace Nextphp\Core\Container;

enum BindingType
{
    case Transient;  // new instance on each make()
    case Singleton;  // one instance per container lifecycle
    case Scoped;     // one instance per request scope
    case Instance;   // pre-built object
}
