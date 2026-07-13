<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Support\ServiceProvider;

class ScrambleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi): void {
                $openApi->info->title = 'Inventory Management API';
                $openApi->info->description = 'Multi-tenant inventory, purchasing, and sales API. Authenticated tenant routes require a Bearer token and `X-Organization-Id` header.';
            })
            ->withOperationTransformers(function (Operation $operation, RouteInfo $routeInfo): void {
                $middleware = collect($routeInfo->route->gatherMiddleware());

                if ($middleware->contains('tenant')) {
                    $operation->addParameters([
                        Parameter::make('X-Organization-Id', 'header')
                            ->description('Active organization ID. The authenticated user must belong to this organization.')
                            ->required(true)
                            ->setSchema(Schema::fromType(new IntegerType))
                            ->example(1),
                    ]);
                }

                if ($middleware->contains('idempotency')) {
                    $operation->addParameters([
                        Parameter::make('Idempotency-Key', 'header')
                            ->description('Unique key for safe retries. Replaying the same key with an identical payload returns the original response with `Idempotency-Replayed: true`.')
                            ->required(true)
                            ->setSchema(Schema::fromType((new StringType)->setMax(255)))
                            ->example('550e8400-e29b-41d4-a716-446655440000'),
                    ]);
                }
            });

        Scramble::afterOpenApiGenerated(function (OpenApi $openApi): void {
            $openApi->components->addSecurityScheme(
                'organization',
                SecurityScheme::apiKey('header', 'X-Organization-Id')
                    ->as('organization')
                    ->setDescription('Organization context for tenant-scoped endpoints.'),
            );
        });
    }
}
