<?php

namespace Pkshetlie\PaginationDbal\Twig\Extension;

use App\Routing\UrlGenerator;
use Pkshetlie\PaginationDbal\Exception\PaginationException;
use Pkshetlie\PaginationDbal\Models\PaginationModel;
use Symfony\Component\HttpFoundation\Request;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class PaginationExtension extends AbstractExtension
{
    private Request $request;
    private UrlGenerator $urlGenerator;

    public function __construct(Request $request, UrlGenerator $urlGenerator)
    {
        $this->request = $request;
        $this->urlGenerator = $urlGenerator;
    }

    public function getFilters()
    {
        return array(
            new TwigFilter('order_link', [$this, 'generateOrderLink'], ['is_safe' => ['html']]),
            new TwigFilter('get_pages', [$this, 'getPages']),
        );
    }

    public function getPages(PaginationModel $pagination, string $etc = '...'): array
    {
        $pid = $pagination->getIdentifier();
        $by = $this->request->query->get('by'.$pid);
        $order = $this->request->query->get('order'.$pid);

        $mergedParameters = array_merge(
            $this->request->query->all(), [
                'ppage'.$pid => 1,
                'order'.$pid => $order,
                'by'.$pid => $by,
            ]
        );
        $pages = [
            [
                'numero' => 1,
                'url' => $this->urlGenerator->generate($this->request->get('_route'), $mergedParameters)
            ]
        ];
        $page = 2;

        if ($pagination->getPages() > 1) {
            while ($page < $pagination->getPages()) {
                if (($page <= $pagination->getCurrent() + 2 && $page >= $pagination->getCurrent() - 2)) {
                    $mergedParameters = array_merge($mergedParameters, ['ppage'.$pid => $page]);
                    $pages[] = [
                        'numero' => $page,
                        'url' => $this->urlGenerator->generate($this->request->get('_route'), $mergedParameters)
                    ];
                } elseif ($pages[count($pages) - 1]['numero'] !== $etc) {
                    $pages[] = ['numero' => $etc, 'url' => null];
                }

                $page++;
            }
        }
        $mergedParameters = array_merge($mergedParameters, ['ppage'.$pid => $pagination->getPages()]);
        $pages[] = [
            'numero' => $pagination->getPages(),
            'url' => $this->urlGenerator->generate($this->request->get('_route'), $mergedParameters)
        ];

        return $pages;
    }

    public function generateOrderLink(
        PaginationModel $pagination,
        string $columnAlias,
        string $label,
        bool $ajax = true
    ) {
        if (!$pagination->getOrderModel()->aliasExists($columnAlias)) {
            throw new PaginationException(
                'Alias "'.$columnAlias.'" undefined in OrderModel, alias defined : [\''.implode(
                    '\', \'',
                    $pagination->getOrderModel()->getAliases()
                ).'\']'
            );
        }

        $request = $this->request;
        $pid = $pagination->getIdentifier();
        $by = $request->query->get('order'.$pid) == $columnAlias &&
        $request->query->get('by'.$pid) == "asc" ? 'desc' : 'asc';

        $mergedParameters = array_merge(
            $request->query->all(), [
                'ppage'.$pid => $request->query->get('ppage'.$pid),
                'order'.$pid => $columnAlias,
                'by'.$pid => $by,
            ]
        );

        $sortClasses = '';

        if ($request->query->get('order'.$pid) == $columnAlias && $request->query->get('by'.$pid)) {
            $sortClasses = ' sortby_active sortby_'.$request->query->get('by'.$pid);
        }

        return '<a href="'.$this->urlGenerator->generate($request->get('_route'), $mergedParameters).
            '" class="sortby'.$sortClasses.''.($ajax ? ' pagination-ajax-sort' : '').'" >'.$label.'</a >';
    }
}