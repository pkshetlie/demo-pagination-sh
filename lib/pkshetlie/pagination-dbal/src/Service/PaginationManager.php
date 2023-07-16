<?php

namespace Pkshetlie\PaginationDbal\Service;

use Pkshetlie\PaginationDbal\Exception\PaginationException;
use App\Routing\UrlGenerator;
use Doctrine\DBAL\Query\QueryBuilder;
use Exception;
use Pkshetlie\PaginationDbal\Models\OrderModel;
use Pkshetlie\PaginationDbal\Models\PaginationModel;
use Pkshetlie\PaginationDbal\Twig\Extension\PaginationExtension;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class PaginationManager
{
    private int $nbItemPerPage = 25;
    /**
     * @var callable
     */
    private $queryPostProcess;

    private Request $request;
    private ?QueryBuilder $queryBuilder = null;

    private PaginationModel $pagination;

    public function __construct(Environment $twig, Request $request, UrlGenerator $urlGenerator)
    {
        /** @var FilesystemLoader $loader */
        $loader = $twig->getLoader();
        $loader->addPath(__DIR__.'/../../twig');
        $twig->addExtension(new PaginationExtension($request, $urlGenerator));

        $this->request = $request;
    }

    public function setQueryBuilder(QueryBuilder $qb): self
    {
        return $this->setQb($qb);
    }

    public function setQb(QueryBuilder $qb): self
    {
        $this->pagination = new PaginationModel();

        $this->queryBuilder = $qb;
        return $this;
    }

    public function setNbItemPerPage(int $nbItemPerPage = 25): self
    {
        $this->nbItemPerPage = $nbItemPerPage;

        return $this;
    }

    public function queryPostProcess(callable $function): self
    {
        $this->queryPostProcess = $function;

        return $this;
    }

    public function process(): PaginationModel
    {
        if(!$this->queryBuilder){
            throw new PaginationException("the QueryBuilder need to be set (->setQB)");
        }

        $countQuery = clone $this->queryBuilder;
        $page = $this->getPage();
        $startAt = $page * $this->nbItemPerPage;

        if ($this->queryBuilder->getQueryPart('having')) {
            throw new PaginationException('Pagination manager n\'est pas compatible avec les requetes comportant des HAVING');
        }

        try {
            $countRslt = $countQuery
                ->select('COUNT(*) as count_nb_elt')
                ->execute()->fetchFirstColumn();
        } catch (Exception $e) {
            throw new PaginationException('Probleme pagination : '.$e->getMessage());
        }

        $nbPages = ceil(($countRslt != null ? $countRslt[0] : 0) / $this->nbItemPerPage);
        $nbPages = max($nbPages, 0);
        $startAt = max($startAt, 0);

        $entities = $this->queryBuilder
            ->setMaxResults($this->nbItemPerPage)
            ->setFirstResult($startAt)
            ->execute()
            ->fetchAllAssociative();

        $this->pagination
            ->setIsPartial($this->isPartial())
            ->setEntities($entities)
            ->setPages($nbPages)
            ->setCount((null !== $countRslt ? $countRslt[0] : 0))
            ->setCurrent($page + 1);

        if (isset($this->queryPostProcess)) {
            call_user_func($this->queryPostProcess, $this->pagination);
        }

        return $this->pagination;
    }

    private function getPage(int $default = 1): int
    {
        $page = $this->request->query->getInt('ppage'.PaginationModel::getStaticIdentifier(), $default) - 1;

        if ($page < 0) {
            $page = 0;
        }

        return $page;
    }

    public function ordering(OrderModel $orderModel, bool $keepNativeOrderBy = false): self
    {
        if(!$this->queryBuilder){
            throw new PaginationException("the QueryBuilder need to be set (->setQB)");
        }
        $this->pagination->setOrderModel($orderModel);

        if ($this->getOrder()) {
            if ($orderModel->aliasExists($this->getOrder())) {
                if (empty($this->queryBuilder->getQueryPart('orderBy')) && $keepNativeOrderBy) {
                    $this->queryBuilder->addOrderBy(
                        $orderModel->getColumn($this->getOrder()),
                        $this->getBy()
                    );
                }else{
                    $this->queryBuilder->orderBy(
                        $orderModel->getColumn($this->getOrder()),
                        $this->getBy()
                    );
                }
            }
        }

        return $this;
    }

    private function getOrder(): ?string
    {
        return $this->request->query->get('order'.PaginationModel::getStaticIdentifier());
    }

    private function isPartial(): string
    {
        return $this->request->query->has('ajax');
    }

    private function getBy(): string
    {
        return $this->request->query->get('by'.PaginationModel::getStaticIdentifier()) == 'asc' ? 'asc' : 'desc';
    }

    private function getBaseAlias(QueryBuilder $qb): string
    {
        if (empty($qb->getQueryPart('from')[0]['alias'])) {
            throw new PaginationException('You need to put an alias in ->from(\'tablename\',\'alias\')');
        }

        return $qb->getQueryPart('from')[0]['alias'];
    }
}
