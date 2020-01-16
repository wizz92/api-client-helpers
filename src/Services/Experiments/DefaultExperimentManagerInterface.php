<?php

namespace Wizz\ApiClientHelpers\Services\Experiments;

interface DefaultExperimentManagerInterface
{
    public function run(Request $request, array $experimentInfo, string $type);
}
