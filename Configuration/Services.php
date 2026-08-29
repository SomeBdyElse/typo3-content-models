<?php

declare(strict_types=1);

use SomeBdyElse\Typo3ContentModels\Contract\ContentModel;
use SomeBdyElse\Typo3ContentModels\Generation\DependencyInjection\FieldGenerationHandlerCompilerPass;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\AsFieldGenerationHandler;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\HandlerInterface;
use SomeBdyElse\Typo3ContentModels\Rendering\DependencyInjection\ContentModelCompilerPass;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator, ContainerBuilder $containerBuilder): void {
    $containerBuilder->registerAttributeForAutoconfiguration(
        AsFieldGenerationHandler::class,
        static function (ChildDefinition $definition, AsFieldGenerationHandler $attribute): void {
            $definition->addTag(HandlerInterface::TAG, [
                'identifier' => $attribute->identifier,
                'before' => implode(',', $attribute->before),
                'after' => implode(',', $attribute->after),
            ]);
        },
    );

    $containerBuilder->registerAttributeForAutoconfiguration(
        ContentModel::class,
        static function (ChildDefinition $definition, ContentModel $attribute): void {
            $definition->addResourceTag(ContentModel::TAG, [
                'table' => $attribute->table,
                'type' => $attribute->type,
            ]);
        },
    );
    $containerBuilder->addCompilerPass(new FieldGenerationHandlerCompilerPass());
    $containerBuilder->addCompilerPass(new ContentModelCompilerPass());
};
