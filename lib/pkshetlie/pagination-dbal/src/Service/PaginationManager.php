<?php

namespace Pkshetlie\PaginationDbal\Service;

use Doctrine\DBAL\Query\QueryBuilder;
use Exception;
use Pkshetlie\PaginationDbal\Models\PaginationModel;
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

    public function __construct(Environment $twig)
    {
        /** @var FilesystemLoader $loader */
        $loader = $twig->getLoader();
        $loader->addPath(__DIR__ . '/../../twig');
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

    public function process(QueryBuilder $queryBuilder, Request $request): PaginationModel
    {
        $pagination = new PaginationModel();
        $pagination->setLastEntityId($request->query->get('plentid' . $pagination->getIdentifier(), 0))->setIsPartial(
                $request->query->get('ppartial' . $pagination->getIdentifier(), false)
            );

        $usableQuery = clone $queryBuilder;
        $key = 'ppage' . $pagination->getIdentifier();
        $page = (!empty($request->query->get($key, 1)) ? $request->query->get($key, 1) : 1) - 1;
        $startAt = $page * $this->nbItemPerPage;

        if ($queryBuilder->getQueryPart('having')) {
            throw new Exception('Pagination manager n\'est pas compatible avec les requetes comportant des HAVING');
        }

        try {
            $countRslt = $usableQuery->select(
                    'COUNT( DISTINCT ' . $this->getBaseAlias($queryBuilder) . '.id ) as count_nb_elt'
                )->execute()->fetchFirstColumn();
        } catch (Exception $e) {
            throw new Exception('Probleme pagination : ' . $e->getMessage());
        }

        $nbPages = ceil(($countRslt != null ? $countRslt[0] : 0) / $this->nbItemPerPage);
        $nbPages = max($nbPages, 0);
        $startAt = max($startAt, 0);

        $entities = $queryBuilder->setMaxResults($this->nbItemPerPage)->setFirstResult($startAt)->execute(
            )->fetchAllAssociative();

        $pagination->setEntities($entities)->setPages($nbPages)->setCount(
                ($countRslt != null ? $countRslt[0] : 0)
            )->setCurrent($page + 1);

        if (isset($this->queryPostProcess)) {
            call_user_func($this->queryPostProcess, $pagination);
        }

        return $pagination;
    }

    private function getBaseAlias(QueryBuilder $qb): string
    {
        if (empty($qb->getQueryPart('from')[0]['alias'])) {
            throw new Exception('You need to put an alias in ->from(\'tablename\',\'alias\')');
        }
        return $qb->getQueryPart('from')[0]['alias'];
    }

    public function ordering(
        QueryBuilder $queryBuilder,
        array $correspondance,
        Request $request,
        array $default = []
    ): self {
        if ($request->query->get('order')) {
            if (isset($correspondance[$request->query->get('order')])) {
                $queryBuilder->orderBy(
                    $correspondance[$request->query->get('order')],
                    $request->query->get('by') == 'asc' ? 'asc' : 'desc'
                );
            }
        }

        if (empty($queryBuilder->getQueryPart('orderBy'))) {
            if (!empty($default)) {
                if (isset($correspondance[$request->query->get('order')])) {
                    $queryBuilder->orderBy($correspondance[$default[0]], $default[1]);
                }
            }
        }

        return $this;
    }
}
