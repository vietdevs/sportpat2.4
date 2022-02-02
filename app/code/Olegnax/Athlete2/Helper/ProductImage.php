<?php
/**
 * Athlete2 Theme
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Olegnax.com license that is
 * available through the world-wide-web at this URL:
 * https://www.olegnax.com/license
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Olegnax
 * @package     Olegnax_Athlete2
 * @copyright   Copyright (c) 2021 Olegnax (http://www.olegnax.com/)
 * @license     https://www.olegnax.com/license
 */

namespace Olegnax\Athlete2\Helper;

use Exception;
use Magento\Catalog\Block\Product\Image as CatalogBlockProductImage;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Image\ParamsBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Config\View;
use Magento\Framework\View\ConfigInterface;

/**
 * Description of Image
 *
 * @author Master
 */
class ProductImage extends AbstractHelper
{

    const TEMPLATE = 'Magento_Catalog::product/image_with_borders.phtml';
    const HOVER_TEMPLATE = 'Magento_Catalog::product/hover_image_with_borders.phtml';
    public $_objectManager;
    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;
    /**
     * @var ConfigInterface
     */
    protected $viewConfig;
    /**
     * @var View
     */
    protected $configView;
    /**
     * @var ParamsBuilder
     */
    private $imageParamsBuilder;

    /**
     * ProductImage constructor.
     * @param Context $context
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param ConfigInterface $viewConfig
     * @param ParamsBuilder $imageParamsBuilder
     */
    public function __construct(
        Context $context,
        \Magento\Catalog\Helper\Image $imageHelper,
        ConfigInterface $viewConfig,
        ParamsBuilder $imageParamsBuilder
    ) {

        $this->_objectManager = ObjectManager::getInstance();
        $this->imageHelper = $imageHelper;
        $this->viewConfig = $viewConfig;
        $this->imageParamsBuilder = $imageParamsBuilder;
        parent::__construct($context);
    }

    /**
     * @param Product $product
     * @param string $imageId
     * @param string $imageIdHover
     * @param string $template
     * @param array $attributes
     * @param array $properties
     * @return mixed
     */
    public function getImageHover(
        Product $product,
        $imageId,
        $imageIdHover,
        $template = self::HOVER_TEMPLATE,
        array $attributes = [],
        $properties = []
    ) {
        if (!$this->hasHoverImage($product, $imageId, $imageIdHover)) {
            return $this->getImage($product, $imageId, self::TEMPLATE, $attributes, $properties);
        }

        $image = $this->_getImage($product, $imageId, $properties)->getUrl();
        $imageMiscParams = $this->getImageParams($imageId);
        $image_hoverMiscParams = $this->getImageParams($imageIdHover);

        $image_hover = $this->resizeImage(
            $product,
            $imageIdHover,
            [
                $imageMiscParams['image_width'],
                $imageMiscParams['image_height'],
            ],
            $properties
        )->getUrl();

        $data = [
            'data' => [
                'template' => $template,
                'product_id' => $product->getId(),
                'product' => $product,
                'image_id' => $imageId,
                'image_hover_id' => $imageIdHover,
                'image_url' => $image,
                'image_hover_url' => $image_hover,
                'label' => $this->getLabel($product, $imageMiscParams['image_type']),
                'label_hover' => $this->getLabel($product, $image_hoverMiscParams['image_type']),
                'width' => $imageMiscParams['image_width'],
                'height' => $imageMiscParams['image_height'],
                'ratio' => $this->getRatio($imageMiscParams['image_width'], $imageMiscParams['image_height']),
                'class' => $this->getClass($attributes),
                'custom_attributes' => $attributes,
            ],
        ];

        return $this->_createTemplate($data);
    }

    /**
     * @param Product $product
     * @param string $imageId
     * @param string $imageId_hover
     * @return bool
     */
    public function hasHoverImage(Product $product, $imageId, $imageId_hover)
    {
        if ($imageId != $imageId_hover) {
            $_imageId = $this->getImageParams($imageId);
            $_imageId_hover = $this->getImageParams($imageId_hover);
            if ($_imageId['image_type'] !== $_imageId_hover['image_type']) {
                $image = $product->getData($_imageId['image_type']);
                $image_hover = $product->getData($_imageId_hover['image_type']);
                return $image && $image_hover && 'no_selection' !== $image_hover && $image !== $image_hover;
            }
        }

        return false;
    }

    /**
     * @param $imageId
     * @return array
     */
    protected function getImageParams($imageId)
    {
        $viewImageConfig = $this->getConfigView()->getMediaAttributes(
            'Magento_Catalog',
            \Magento\Catalog\Helper\Image::MEDIA_TYPE_CONFIG_NODE,
            $imageId
        );
        $imageMiscParams = $this->imageParamsBuilder->build($viewImageConfig);
        if (empty($imageMiscParams)) {
            $imageMiscParams = $this->getDefaultParams();
            $this->_logger->critical(sprintf('No options found for "%s" images!', $imageId));
        }

        return $imageMiscParams;
    }

    /**
     * Retrieve config view
     *
     * @return View
     */
    protected function getConfigView()
    {
        if (!$this->configView) {
            $this->configView = $this->viewConfig->getViewConfig();
        }
        return $this->configView;
    }

    /**
     * @return array
     */
    protected function getDefaultParams()
    {
        return [
            "image_type" => "small_image",
            "image_height" => 240,
            "image_width" => 240,
            "background" => [255, 255, 255],
            "quality" => 80,
            "keep_aspect_ratio" => true,
            "keep_frame" => true,
            "keep_transparency" => true,
            "constrain_only" => true,
        ];
    }

    /**
     * @param Product $product
     * @param string $imageId
     * @param string $template
     * @param array $attributes
     * @param array $properties
     * @return mixed
     */
    public function getImage(
        Product $product,
        $imageId,
        $template = self::TEMPLATE,
        array $attributes = [],
        $properties = []
    ) {
        $image = $this->_getImage($product, $imageId, $properties);
        $imageMiscParams = $this->getImageParams($imageId);

        $data = [
            'data' => [
                'template' => $template,
                'product_id' => $product->getId(),
                'product' => $product,
                'image_id' => $imageId,
                'image_url' => $image->getUrl(),
                'label' => $this->getLabel($product, $imageMiscParams['image_type']),
                'width' => $imageMiscParams['image_width'],
                'height' => $imageMiscParams['image_height'],
                'ratio' => $this->getRatio($imageMiscParams['image_width'], $imageMiscParams['image_height']),
                'class' => $this->getClass($attributes),
                'custom_attributes' => $attributes,
            ],
        ];

        return $this->_createTemplate($data);
    }

    /**
     * @param Product $product
     * @param string $imageId
     * @param array $properties
     * @return \Magento\Catalog\Helper\Image
     */
    private function _getImage(Product $product, $imageId, $properties = [])
    {
        return $this->imageHelper->init($product, $imageId, $properties);
    }

    /**
     * @param Product $product
     *
     * @param string $imageType
     * @return string
     */
    private function getLabel(Product $product, string $imageType): string
    {
        $label = $product->getData($imageType . '_' . 'label');
        if (empty($label)) {
            $label = $product->getName();
        }
        return (string)$label;
    }

    /**
     * Calculate image ratio
     *
     * @param $width
     * @param $height
     * @return float
     */
    private function getRatio(int $width, int $height): float
    {
        if ($width && $height) {
            return $height / $width;
        }
        return 1.0;
    }

    /**
     * @param array $data
     * @return CatalogBlockProductImage
     */
    private function _createTemplate($data = [])
    {
        return $this->_objectManager->create(CatalogBlockProductImage::class, $data);
    }

    /**
     * @param Product $product
     * @param string $imageId
     * @param array|int $size
     * @param array $properties
     * @return \Magento\Catalog\Helper\Image
     */
    public function resizeImage(Product $product, $imageId, $size, $properties = [])
    {
        $size = $this->prepareSize($size);
        $image = $this->_getImage($product, $imageId, $properties);
        $image->resize($size[0], $size[1]);

        return $image;
    }

    /**
     * @param array|int $size
     * @return array
     */
    private function prepareSize($size)
    {
        if (is_array($size) && 1 >= count($size)) {
            $size = array_shift($size);
        }
        if (!is_array($size)) {
            $size = [$size, $size];
        }
        $size = array_map('floatval', $size);
        $size = array_map('abs', $size);
        return $size;
    }

    /**
     * @param Product $product
     * @param string $imageId
     * @param string $imageId_hover
     * @param array|int $size
     * @param string $template
     * @param array $attributes
     * @param array $properties
     * @return CatalogBlockProductImage|mixed
     */
    public function getResizedImageHover(
        Product $product,
        $imageId,
        $imageId_hover,
        $size,
        $template = self::HOVER_TEMPLATE,
        array $attributes = [],
        $properties = []
    ) {
        if (!$this->hasHoverImage($product, $imageId, $imageId_hover)) {
            return $this->getResizedImage($product, $imageId, $size, self::TEMPLATE, $attributes, $properties);
        }
        $imageMiscParams = $this->getImageParams($imageId);
        if (empty($size)) {
            $size = [$imageMiscParams['image_width'], $imageMiscParams['image_height']];
        } elseif (is_array($size)) {
            foreach (['image_width', 'image_height'] as $key => $value) {
                if (!isset($size[$key]) || empty($size[$key])) {
                    $size[$key] = $imageMiscParams[$value];
                }
            }
        }

        $image = $this->resizeImage($product, $imageId, $size, $properties);
        try {
            [$imageMiscParams['image_width'], $imageMiscParams['image_height']] = $image->getResizedImageInfo();
        } catch (Exception $e) {
            $this->_logger->error("OX Product Image: " . $e->getMessage());
            $imageMiscParams['image_width'] = $imageMiscParams['image_height'] = 1;
        }
        $image = $image->getUrl();
        $image_hover = $this->resizeImage($product, $imageId_hover, $size, $properties)->getUrl();
        $image_hoverMiscParams = $this->getImageParams($imageId_hover);

        $data = [
            'data' => [
                'template' => $template,
                'product_id' => $product->getId(),
                'product' => $product,
                'image_id' => $imageId,
                'image_hover_id' => $imageId_hover,
                'image_url' => $image,
                'image_hover_url' => $image_hover,
                'label' => $this->getLabel($product, $imageMiscParams['image_type']),
                'label_hover' => $this->getLabel($product, $image_hoverMiscParams['image_type']),
                'width' => $imageMiscParams['image_width'],
                'height' => $imageMiscParams['image_height'],
                'ratio' => $this->getRatio($imageMiscParams['image_width'], $imageMiscParams['image_height']),
                'class' => $this->getClass($attributes),
                'custom_attributes' => $attributes,
            ],
        ];

        return $this->_createTemplate($data);
    }

    /**
     * @param Product $product
     * @param string $imageId
     * @param array|int $size
     * @param string $template
     * @param array $attributes
     * @param array $properties
     * @return CatalogBlockProductImage|mixed
     */
    public function getResizedImage(
        Product $product,
        $imageId,
        $size,
        $template = self::TEMPLATE,
        array $attributes = [],
        $properties = []
    ) {
        $imageMiscParams = $this->getImageParams($imageId);
        if (empty($size)) {
            return $this->getImage($product, $imageId, $template, $attributes, $properties);
        }
        if (is_array($size)) {
            foreach (['image_width', 'image_height'] as $key => $value) {
                if (!isset($size[$key]) || empty($size[$key])) {
                    $size[$key] = $imageMiscParams[$value];
                }
            }
        }
        $image = $this->resizeImage($product, $imageId, $size, $properties);
        $imageMiscParams = $this->getImageParams($imageId);
        try {
            [$imageMiscParams['image_width'], $imageMiscParams['image_height']] = $image->getResizedImageInfo();
        } catch (Exception $e) {
            $this->_logger->error("OX Product Image: " . $e->getMessage());
            $imageMiscParams['image_width'] = $imageMiscParams['image_height'] = 1;
        }

        $data = [
            'data' => [
                'template' => $template,
                'product_id' => $product->getId(),
                'product' => $product,
                'image_id' => $imageId,
                'image_url' => $image->getUrl(),
                'label' => $this->getLabel($product, $imageMiscParams['image_type']),
                'width' => $imageMiscParams['image_width'],
                'height' => $imageMiscParams['image_height'],
                'ratio' => $this->getRatio($imageMiscParams['image_width'], $imageMiscParams['image_height']),
                'class' => $this->getClass($attributes),
                'custom_attributes' => $attributes,
            ],
        ];

        return $this->_createTemplate($data);
    }

    /**
     * Retrieve image class for HTML element
     *
     * @param array $attributes
     * @return string
     */
    private function getClass(array $attributes): string
    {
        return $attributes['class'] ?? 'product-image-photo';
    }

    /**
     * @param Product $product
     * @param string $image
     * @param array|int $size
     * @param array $properties
     * @return string
     */
    public function getUrlResizedImage(Product $product, $image, $size, $properties = [])
    {
        $image = $this->resizeImage($product, $image, $size, $properties);
        return $image->getUrl();
    }
}
