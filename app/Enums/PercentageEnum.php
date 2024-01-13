<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PercentageEnum: string implements HasLabel
{
   

    public function getLabel(): ?string
    {
        return $this->name;
    }

}
