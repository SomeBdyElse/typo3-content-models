<?php

declare(strict_types=1);

use SomeBdyElse\Typo3ContentModels\Rendering\DependencyInjection\ContentModelCompilerPass;
use SomeBdyElse\Typo3ContentModels\Contract\ContentModel;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator, ContainerBuilder $containerBuilder): void {
    $containerBuilder->registerAttributeForAutoconfiguration(
        ContentModel::class,
        static function (ChildDefinition $definition, ContentModel $attribute): void {
            $definition->addResourceTag(ContentModel::TAG, [
                'table' => $attribute->table,
                'type' => $attribute->type,
            ]);
        },
    );
    $containerBuilder->addCompilerPass(new ContentModelCompilerPass());
};
