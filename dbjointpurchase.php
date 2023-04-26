<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    DevBlinders <soporte@devblinders.com>
 * @copyright Copyright (c) DevBlinders
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;

use Language;

class Dbjointpurchase extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        if (file_exists(dirname(__FILE__) . '/premium/DbPremium.php')) {
            require_once(dirname(__FILE__) . '/premium/DbPremium.php');
            $this->premium = 1;
        } else {
            $this->premium = 0;
        }

        $this->name = 'dbjointpurchase';
        $this->tab = 'front_office_features';
        $this->version = '1.0.1';
        $this->author = 'DevBlinders';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('DB Joint Purchase');
        $this->description = $this->l('Compra conjunta de los productos');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('DBJOINT_COLOR', '#2fb5d2');
        Configuration::updateValue('DBJOINT_EXCLUDE', '');

        include(dirname(__FILE__).'/sql/install.php');
        
        return parent::install() &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayBackOfficeHeader') &&
            $this->registerHook('displayFooterProduct') &&
            $this->registerHook('actionAdminControllerSetMedia');
    }

    public function uninstall()
    {
        include(dirname(__FILE__).'/sql/uninstall.php');
        
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitDbjointpurchaseModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('name_module', $this->name);
        $this->context->smarty->assign('premium', $this->premium);
        $iframe_top = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/iframe.tpl');
        $iframe_bottom = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/iframe_bottom.tpl');

        return $iframe_top . $this->renderForm() . $iframe_bottom;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitDbjointpurchaseModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'color',
                        'label' => $this->l('Color general'),
                        'desc' => $this->l('Color pricipal utilizado en elementos del módulo'),
                        'name' => 'DBJOINT_COLOR',
                        'class' => 'disabled',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Productos excluidos'),
                        'desc' => $this->l(
                            'Insertar las ids de productos excluidos separados por comas. ejem: 18,25,192'
                        ),
                        'name' => 'DBJOINT_EXCLUDE',
                        'class' => 'disabled',
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
        if ($this->premium == 1) {
            $fields_form = DbjointpurchasePremium::renderHelperFormPremium($fields_form);
        }

        return $fields_form;
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'DBJOINT_COLOR' => Configuration::get('DBJOINT_COLOR'),
            'DBJOINT_EXCLUDE' => Configuration::get('DBJOINT_EXCLUDE'),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the BO.
     */
    public function hookDisplayBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name || Tools::getValue('configure') == $this->name) {
            $this->context->controller->addJquery();
            $this->context->controller->addJS($this->_path . '/views/js/back.js');
            $this->context->controller->addCSS($this->_path . '/views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookDisplayHeader()
    {
        $this->context->controller->addJS($this->_path . 'views/js/dbjointpurchase.js');
        $this->context->controller->addCSS($this->_path . 'views/css/dbjointpurchase.css');
        Media::addJsDef(array(
                            'dbjointpurchase_ajax' => Context::getContext()->link->getModuleLink(
                                'dbjointpurchase',
                                'ajax',
                                array()
                            ),
                        ));

        $color_joint = Configuration::get('DBJOINT_COLOR');
        if (empty($color_joint)) {
            $color_joint = '#2fb5d2';
        }
        $inline = '<style>
            :root {
                --dbjoint_color: ' . $color_joint . ';
            }
        </style>';
        return $inline;
    }

    public function hookDisplayFooterProduct($params)
    {
        $product = $params['product'];
        $id_product = $params['product']->id;
        $key = 'dbjointpurchase|' . $id_product;
        $total_price = $params['product']->price_amount;
        $products_cat = $this->getProductsGenerate($id_product);
        if ($products_cat != false && $product['add_to_cart_url']) {
            if (!$this->isCached(
                'module:dbjointpurchase/views/templates/hook/jointpurchase.tpl',
                $this->getCacheId($key)
            )) {
                $productos = [];
                foreach ($products_cat as $key => $products) {
                    $productos[$key] = $this->prepareBlocksProducts($products);
                    foreach ($productos[$key] as $pr) {
                        $total_price += $pr['price_amount'];
                    }
                }
                $this->smarty->assign(array(
                                          'productos' => $productos,
                                          'total_price' => $total_price,
                                          'premium' => $this->premium,
                                      ));
            }

            return $this->fetch('module:dbjointpurchase/views/templates/hook/jointpurchase.tpl');
        }
    }

    public function hookDisplayProductFullWidth($params)
    {
        return $this->hookDisplayFooterProduct($params);
    }

    public function hookDisplayProductMiddle($params)
    {
        return $this->hookDisplayFooterProduct($params);
    }

    /**
     * Devuelve los 3 productos más vendidos junto al producto dato, y si no los hubiera,
     * los más vendidos globalmente.
     * 
     * @param int   $id_product         El id del producto sobre el que buscar el top ventas
     * @param bool  $force_top_sellers  Si está a true, devuelve los 3 top ventas global
     * 
     * @return false|array              Array indexado por categorías/posicion o false si no hay resultados
     */
    public function getProductsGenerate($id_product, $force_top_sellers = false)
    {
        $excludes = $this->getProductsExcludes();

        $products = [];

        if(!$force_top_sellers) {

            $sql = "SELECT od.product_id, count(od.product_id) as total, p.price, p.id_category_default
                FROM " . _DB_PREFIX_ . "order_detail od 
                LEFT JOIN " . _DB_PREFIX_ . "product_shop p ON od.product_id = p.id_product
                " . Shop::addSqlAssociation('product', 'p') . "
                " . Product::sqlStock('p', 0) . "
                WHERE od.product_id > 0 
                AND od.product_id <> '$id_product' 
                AND od.id_order IN (SELECT id_order 
                    FROM " . _DB_PREFIX_ . "order_detail 
                    WHERE product_id = '$id_product' 
                    GROUP BY id_order)
                AND p.active = 1 
                AND p.available_for_order = 1  
                AND p.visibility != 'none' 
                AND p.price > 0
                AND (stock.out_of_stock = 1 OR stock.quantity > 0)";
            if (!empty($excludes)) {
                $sql .= " AND od.product_id NOT IN (" . $excludes . ")";
            }
            $sql .= "GROUP BY p.id_category_default
            HAVING COUNT(*) > 1 
            ORDER BY total DESC
            LIMIT 3";
            $results = Db::getInstance()->ExecuteS($sql);
            if (count($results) >= 1) {
                foreach ($results as $row) {
                    $products[$row['id_category_default']][] = array(
                        'id_product' => $row['product_id'],
                        'price' => $row['price'],
                    );
                }
                
                return $products;
            }
        } 
        //$product = new Product($id_product);
        //$id_category_default = $product->id_category_default;

        // Si no hay productos relacionados en los pedidos buscamos el top ventas de la categoria asociada
        $sql = "SELECT od.product_id, count(od.product_id) as total, p.price, p.id_category_default
            FROM " . _DB_PREFIX_ . "order_detail od
            LEFT JOIN " . _DB_PREFIX_ . "product p ON od.product_id = p.id_product
            LEFT JOIN " . _DB_PREFIX_ . "product_shop ps ON od.product_id = ps.id_product
            " . Shop::addSqlAssociation('product', 'p') . "
            " . Product::sqlStock('p', 0) . "
            WHERE ps.active = 1 
                AND p.available_for_order = 1  
                AND p.visibility != 'none' 
                AND p.price > 0
                AND (stock.out_of_stock = 1 OR stock.quantity > 0)";
        if (!empty($excludes)) {
            $sql .= " AND od.product_id NOT IN (" . $excludes . ")";
        }
        $sql .= " GROUP BY od.product_id 
            HAVING COUNT(*) > 1 
            ORDER BY total DESC
            LIMIT 3";
        $results = Db::getInstance()->ExecuteS($sql);
        if (count($results) >= 1) {
            foreach ($results as $key => $row) {
                $products[$key][] = array(
                    'id_product' => $row['product_id'],
                    'price' => $row['price'],
                );
            }
            return $products;
        }
        return false;
    }

    public function getTopSellerByCategory($id_product)
    {
        if ($this->premium == 1) {
            return DbJointPurchasePremium::getChange($id_product);
        }
        return false;
    }

    public function prepareBlocksProducts($products)
    {
        if ($products != false) {
            $products_for_template = [];
            $assembler = new ProductAssembler($this->context);
            $presenterFactory = new ProductPresenterFactory($this->context);
            $presentationSettings = $presenterFactory->getPresentationSettings();
            $presenter = new ProductListingPresenter(
                new ImageRetriever($this->context->link),
                $this->context->link,
                new PriceFormatter(),
                new ProductColorsRetriever(),
                $this->context->getTranslator()
            );
            $products_for_template = [];
            foreach ($products as $rawProduct) {
                if ($rawProduct['id_product'] > 0) {
                    $products_for_template[] = $presenter->present(
                        $presentationSettings,
                        $assembler->assembleProduct($rawProduct),
                        $this->context->language
                    );
                }
            }

            return $products_for_template;
        } else {
            return false;
        }
    }

    public function renderJointModal($products, $key, $id_best_product, $id_current_product)
    {
        if ($this->premium == 1) {
            $bestproduct = [];
            $bestproduct[] = array('id_product' => $id_best_product);
            $products = array_merge($bestproduct, $products);
            $productos = $this->prepareBlocksProducts($products);

            $this->smarty->assign(array(
                                      'productos' => $productos,
                                      'key' => $key,
                                      'current_product' => $id_current_product,
                                      'premium' => $this->premium,
                                  ));
            return $this->fetch('module:dbjointpurchase/views/templates/hook/modal_joint.tpl');
        }

        return false;
    }

    public function renderJointProduct($id_product, $key)
    {
        $products = [];
        $products[] = array('id_product' => $id_product);
        $productos = $this->prepareBlocksProducts($products);

        $this->smarty->assign(array(
                                  'products' => $productos,
                                  'i' => $key,
                                  'premium' => $this->premium,
                              ));
        return $this->fetch('module:dbjointpurchase/views/templates/hook/product_joint.tpl');
    }

    public function getProductsExcludes()
    {
        $excludes = Configuration::get('DBJOINT_EXCLUDE');
        if (!empty($excludes)) {
            $excludes = explode(',', $excludes);
            $excludes = array_values(array_filter(array_map('trim', $excludes), 'strlen'));
            $excludes = array_filter($excludes, 'is_numeric');
            $excludes = array_unique($excludes);
            $excludes = implode(',', $excludes);
        }

        return $excludes;
    }

    /**
     * Hook to charge JS file in back office (only in product pages)
     */
    public function hookActionAdminControllerSetMedia()
    {
        if($this->context->controller->controller_name != 'AdminProducts')
        {
            return;
        }
        
        $controller_link = $this->context->link->getModuleLink($this->name, 'save');

        global $kernel;
        $requestStack = $kernel->getContainer()->get('request_stack');
        $request = $requestStack->getCurrentRequest();
        $idProduct = $request->get('id');

        // Obtenemos los productos candidatos a ser elegidos:
        //  - Productos que genera el módulo
        //  - Productos Top ventas 
        $products_cat = $this->getProductsGenerate($idProduct);
        $products_top = $this->getProductsGenerate($idProduct, true);
        
        // Construimos un array de todos los productos indexados por su id_product
        $products_cat = $this->getProductsByTag($products_cat, "cat");
    
        $products_top = $this->getProductsByTag($products_top, "top");
        
        // Unimos los arrays para evitar duplicados
        $productos = $products_cat + $products_top;
        
        Media::addJsDef([   'joints' => $productos, 
                            'controller_link' => $controller_link, 
                            'product' => $idProduct,
                        ]);

        $this->context->controller->addJS($this->local_path . 'views/js/select_back.js');
        $this->context->controller->addCSS($this->local_path . 'views/css/select_back.css');
    }

    /**
     * Devuelve un array indexado por los id_product, conteniendo el tag indicado como valor
     * 
     * @param $products_tag     array indexado por un valor de tag
     * @param $tag              etiqueta que se asignará como valor de todos los productos
     * 
     * @return false|array      Si no existe el índice de algun producto devuelve false
     */
    public function getProductsByTag($products_tag, $tag)
    {
        if(empty($products_tag)) {
            return [];
        }
        $productos = [];
        foreach ($products_tag as $products) {
            foreach($products as $product) {
                if(!isset($product['id_product'])) {
                    return false;
                }
                $productos[$product['id_product']] = $tag;
            }
        }
        return $productos;
    }
}
