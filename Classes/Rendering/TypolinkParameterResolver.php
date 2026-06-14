<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Rendering;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\LinkHandling\TypoLinkCodecService;
use TYPO3\CMS\Core\LinkHandling\TypolinkParameter;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class TypolinkParameterResolver
{
    public function resolve(ServerRequestInterface $request, TypolinkParameter $value): string
    {
        $tsfe = $request->getAttribute('frontend.controller');

        $typoLinkCodecService = GeneralUtility::makeInstance(TypoLinkCodecService::class);
        $parameter = $typoLinkCodecService->encode($value->toArray());

        return $tsfe->cObj->createUrl([
            'parameter' => $parameter,
        ]);
    }
}
