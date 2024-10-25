<?php

class FullNameOrder extends Module
{
    public function __construct()
    {
        $this->name = 'fullnameorder';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Kjeld Borch Egevang';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Full Name Order');
        $this->description = $this->l(
            'Show full customer name in order list.'
        );
        $this->confirmUninstall = $this->l(
            'Are you sure you want to uninstall?'
        );
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('actionOrderGridQueryBuilderModifier');
    }

    public function getCustomerField()
    {
        return 'CONCAT(cu.`firstname`, " ", cu.`lastname`)';
    }

    public function hookActionOrderGridQueryBuilderModifier($params)
    {
        $sc = $params['search_criteria'];
        $filters = $sc->getFilters();
        $qb = $params['search_query_builder'];
        $qb->where('TRUE');
        $qb->select()
            ->addSelect($this->getCustomerField() . ' AS `customer`')
            ->addSelect('o.id_order, o.reference, o.total_paid_tax_incl, os.paid, osl.name AS osname')
            ->addSelect('o.id_currency, cur.iso_code')
            ->addSelect('o.current_state, o.id_customer')
            ->addSelect('cu.`id_customer` IS NULL as `deleted_customer`')
            ->addSelect('os.color, o.payment, s.name AS shop_name')
            ->addSelect('o.date_add, cu.company, cl.name AS country_name, o.invoice_number, o.delivery_number');

        $strictComparisonFilters = [
            'id_order' => 'o.id_order',
            'country_name' => 'c.id_country',
            'total_paid_tax_incl' => 'o.total_paid_tax_incl',
            'osname' => 'os.id_order_state',
        ];

        $likeComparisonFilters = [
            'reference' => 'o.`reference`',
            'company' => 'cu.`company`',
            'payment' => 'o.`payment`',
            'customer' => $this->getCustomerField(),
        ];

        $havingLikeComparisonFilters = [];

        $dateComparisonFilters = [
            'date_add' => 'o.`date_add`',
        ];

        foreach ($filters as $filterName => $filterValue) {
            if (isset($strictComparisonFilters[$filterName])) {
                $alias = $strictComparisonFilters[$filterName];

                $qb->andWhere("$alias = :$filterName");
                $qb->setParameter($filterName, $filterValue);

                continue;
            }

            if (isset($likeComparisonFilters[$filterName])) {
                $alias = $likeComparisonFilters[$filterName];

                $qb->andWhere("$alias LIKE :$filterName");
                $qb->setParameter($filterName, '%' . $filterValue . '%');

                continue;
            }

            if (isset($havingLikeComparisonFilters[$filterName])) {
                $alias = $havingLikeComparisonFilters[$filterName];

                $qb->andHaving("$alias LIKE :$filterName");
                $qb->setParameter($filterName, '%' . $filterValue . '%');

                continue;
            }

            if (isset($dateComparisonFilters[$filterName])) {
                $alias = $dateComparisonFilters[$filterName];

                if (isset($filterValue['from'])) {
                    $name = sprintf('%s_from', $filterName);

                    $qb->andWhere("$alias >= :$name");
                    $qb->setParameter(
                        $name,
                        sprintf('%s %s', $filterValue['from'], '0:0:0')
                    );
                }

                if (isset($filterValue['to'])) {
                    $name = sprintf('%s_to', $filterName);

                    $qb->andWhere("$alias <= :$name");
                    $qb->setParameter(
                        $name,
                        sprintf('%s %s', $filterValue['to'], '23:59:59')
                    );
                }

                continue;
            }
        }
    }
}
