<?php

namespace Tests\Sample;

use ByJG\Serializer\BaseModel;

class ModelPublic
{

    public ?int $Id = null;
    public ?string $Name = null;

    /**
     * ModelPublic constructor.
     * @param ?int $Id
     * @param ?string $Name
     */
    public function __construct(?int $Id, ?string $Name)
    {
        $this->Id = $Id;
        $this->Name = $Name;
    }
}
