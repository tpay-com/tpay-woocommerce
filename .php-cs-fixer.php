<?php

require __DIR__.'/vendor/tpay-com/coding-standards/bootstrap.php';

$config = Tpay\CodingStandards\PhpCsFixerConfigFactory::createWithLegacyRules();

return $config
    ->setRules(['phpdoc_tag_no_named_arguments' => false] + $config->getRules())
    ->setFinder(PhpCsFixer\Finder::create()->ignoreDotFiles(false)->in(__DIR__.'/includes'));
