<?php declare(strict_types=1);

namespace Shopware\Core\Content\Breadcrumb\SalesChannel;

use Shopware\Core\Content\Breadcrumb\Struct\BreadcrumbCollection;
use Shopware\Core\Content\Category\Service\CategoryBreadcrumbBuilder;
use Shopware\Core\Content\Product\Exception\ProductNotFoundException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['store-api']])]
#[Package('inventory')]
class BreadcrumbRoute extends AbstractBreadcrumbRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly CategoryBreadcrumbBuilder $breadcrumbBuilder,
    ) {
    }

    public function getDecorated(): AbstractBreadcrumbRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[Route(path: '/store-api/breadcrumb/{id}', name: 'store-api.breadcrumb', requirements: ['id' => '[0-9a-f]{32}'], methods: ['GET'])]
    public function load(Request $request, SalesChannelContext $salesChannelContext): BreadcrumbRouteResponse
    {
        $id = $request->get('id', '');
        $type = $request->get('type', 'product');
        if ($type === 'category') {
            $breadcrumb = $this->getCategories($id, $salesChannelContext);
        } else {
            $breadcrumb = $this->tryToGetCategoriesFromProductOrCategory(
                $id,
                $request->get('referrerCategoryId', ''),
                $salesChannelContext
            );
        }

        return new BreadcrumbRouteResponse($breadcrumb);
    }

    private function getCategories(string $id, SalesChannelContext $salesChannelContext): BreadcrumbCollection
    {
        $category = $this->breadcrumbBuilder->loadCategory($id, $salesChannelContext->getContext());

        if ($category === null) {
            return new BreadcrumbCollection();
        }

        return $this->breadcrumbBuilder->getCategoryBreadcrumbUrls(
            $category,
            $salesChannelContext->getContext(),
            $salesChannelContext->getSalesChannel()
        );
    }

    /**
     * Simple helper function to retry with category type if product is not found
     */
    private function tryToGetCategoriesFromProductOrCategory(string $id, string $referrerCategoryId, SalesChannelContext $salesChannelContext): BreadcrumbCollection
    {
        try {
            $categories = $this->breadcrumbBuilder->getProductBreadcrumbUrls($id, $referrerCategoryId, $salesChannelContext);
        } catch (ProductNotFoundException) {
            $categories = $this->getCategories($id, $salesChannelContext);
        }

        return $categories;
    }
}
