<?php

namespace NGFramer\NGFramerPHPSQLBuilder\DataDefinition;

class CreateView extends _DdlCommon
{
    public function select(string $selectQuery): self
    {
        $this->logViewValue($selectQuery);
        return $this;
    }

    public function build(): string
    {
        return "";
    }
}