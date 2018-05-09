<?php
namespace Sellastica\Latte;

class LatteFilters implements \Contributte\Latte\Filters\FiltersProvider
{
	/** @var \Nette\Http\Request */
	private $request;
	/** @var \Sellastica\Currency\Model\CurrencyAccessor */
	private $currencyAccessor;
	/** @var \Sellastica\Localization\Model\LocalizationAccessor */
	private $localizationAccessor;
	/** @var \Sellastica\Project\Model\SettingsAccessor */
	private $settingsAccessor;
	/** @var \Nette\Localization\ITranslator */
	private $translator;
	/** @var \Sellastica\Thumbnailer\Thumbnailer */
	private $thumbnailer;
	/** @var \Sellastica\Thumbnailer\ThumbnailerAccessor */
	private $thumbnailerAccessor;


	/**
	 * @param \Nette\Http\Request $request
	 * @param \Sellastica\Currency\Model\CurrencyAccessor $currencyAccessor
	 * @param \Sellastica\Localization\Model\LocalizationAccessor $localizationAccessor
	 * @param \Sellastica\Project\Model\SettingsAccessor $settingsAccessor
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \Sellastica\Thumbnailer\ThumbnailerAccessor $thumbnailerAccessor
	 */
	public function __construct(
		\Nette\Http\Request $request,
		\Sellastica\Currency\Model\CurrencyAccessor $currencyAccessor,
		\Sellastica\Localization\Model\LocalizationAccessor $localizationAccessor,
		\Sellastica\Project\Model\SettingsAccessor $settingsAccessor,
		\Nette\Localization\ITranslator $translator,
		\Sellastica\Thumbnailer\ThumbnailerAccessor $thumbnailerAccessor
	)
	{
		$this->request = $request;
		$this->currencyAccessor = $currencyAccessor;
		$this->localizationAccessor = $localizationAccessor;
		$this->settingsAccessor = $settingsAccessor;
		$this->translator = $translator;
		$this->thumbnailerAccessor = $thumbnailerAccessor;
	}

	/**
	 * @return \Sellastica\Thumbnailer\Thumbnailer
	 */
	private function getThumbnailer(): \Sellastica\Thumbnailer\Thumbnailer
	{
		if (!isset($this->thumbnailer)) {
			$this->thumbnailer = $this->thumbnailerAccessor->get();
		}

		return $this->thumbnailer;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getFilters()
	{
		return [
			'bytes' => function ($bytes, $precision = 2) {
				return $this->bytes($bytes, $precision);
			},
			'crop' => function ($image, int $width, int $height) {
				return $this->resize($image, $width, $height, \Sellastica\Thumbnailer\Thumbnailer::CROP);
			},
			'date' => function (\DateTime $date, $format = null) {
				return $this->date($date, $format);
			},
			'date_time' => function (\DateTime $date) {
				return $this->date_time($date);
			},
			'date_time_pretty' => function (\DateTime $date) {
				return $this->date_time_pretty($date);
			},
			'date_time_with_seconds' => function (\DateTime $date) {
				return $this->date_time_with_seconds($date);
			},
			'money' => function ($number, $currency = null) {
				return $this->money($number, $currency);
			},
			'price' => function ($number, $currency = null) {
				return $this->price($number, $currency);
			},
			'resize' => function ($image, int $width, int $height) {
				return $this->resize($image, $width, $height, \Sellastica\Thumbnailer\Thumbnailer::RESIZE);
			},
			'resize_exact' => function ($image, int $width, int $height) {
				return $this->resize($image, $width, $height, \Sellastica\Thumbnailer\Thumbnailer::EXACT);
			},
			'round' => function (float $number, int $precision = 0) {
				return $this->round($number, $precision);
			},
			'time' => function (\DateTime $date) {
				return $this->time($date);
			},
			'time_ago_in_words' => function ($time) {
				return $this->time_ago_in_words($time);
			},
			'time_with_seconds' => function (\DateTime $date) {
				return $this->time_with_seconds($date);
			},
		];
	}

	/**
	 * @return \Sellastica\Localization\Model\Localization
	 */
	private function getLocalization()
	{
		return $this->localizationAccessor->get();
	}

	/**
	 * @param float $bytes
	 * @param int $precision
	 * @return string
	 */
	private function bytes($bytes, $precision = 2)
	{
		return \Sellastica\Utils\Numbers::bytesToSize($bytes, $precision);
	}

	/**
	 * @param \Sellastica\Price\Price|float $number
	 * @param \Sellastica\Localization\Model\Currency $currency
	 * @param bool $trimIntegers
	 * @param bool $symbol Display incl. symbol
	 * @return string
	 * @internal
	 */
	private function moneyFormat(
		$number,
		\Sellastica\Localization\Model\Currency $currency = null,
		$trimIntegers = true,
		$symbol = true
	)
	{
		if ($number instanceof \Sellastica\Price\Price) {
			$number = $number->getDefaultPrice();
		}

		if (isset($currency)) {
			return $currency->format($number, $trimIntegers, $symbol);
		}

		return $this->currencyAccessor->getDefaultCurrency()->format($number, $trimIntegers, $symbol);
	}

	/**
	 * @param \Sellastica\Price\Price|float $number
	 * @param \Sellastica\Localization\Model\Currency $currency
	 * @return string
	 */
	private function money($number, \Sellastica\Localization\Model\Currency $currency = null)
	{
		return $this->moneyFormat($number, $currency);
	}

	/**
	 * @param \Sellastica\Price\Price|float $number
	 * @param \Sellastica\Localization\Model\Currency $currency
	 * @return string
	 */
	private function price($number, \Sellastica\Localization\Model\Currency $currency = null)
	{
		return $this->moneyFormat($number, $currency, true, false);
	}

	/**
	 * @param mixed $time
	 * @return string
	 */
	private function time_ago_in_words($time)
	{
		//todo
	}

	/**
	 * @param \DateTime $date
	 * @param string $format
	 * @return string
	 */
	private function date(\DateTime $date, $format = null)
	{
		$format = $format ?: $this->getLocalization()->getDateFormat();
		return $date->format($format);
	}

	/**
	 * @param $image
	 * @param int $width
	 * @param int $height
	 * @param string $operation
	 * @return string
	 * @throws \Exception
	 * @throws \InvalidArgumentException
	 */
	private function resize($image, int $width, int $height, string $operation)
	{
		$options = new \Sellastica\Thumbnailer\Options($operation, $width, $height);
		try {
			if ($image instanceof \Sellastica\Core\IImage) {
				return $this->getThumbnailer()->create($image->getUrl()->getAbsoluteUrl(), $options);
			} elseif (!isset($image)) {
				return $this->getThumbnailer()->createPlaceholder($width, $height);
			} elseif (is_string($image) && \Nette\Utils\Validators::isUrl($image)) {
				return $this->getThumbnailer()->create($image, $options);
			} elseif ($image instanceof \Nette\Http\Url) {
				return $this->getThumbnailer()->create($image->getAbsoluteUrl(), $options);
			}
		} catch (\Sellastica\Thumbnailer\Exception\ThumbnailerException $e) {
			return $this->getThumbnailer()->createPlaceholder($width, $height);
		}

		throw new \InvalidArgumentException('Invalid image parameter');
	}

	/**
	 * @param \DateTime $date
	 * @return string
	 */
	private function time(\DateTime $date)
	{
		return $date->format($this->getLocalization()->getTimeFormat());
	}

	/**
	 * @param \DateTime $date
	 * @return string
	 */
	private function time_with_seconds(\DateTime $date)
	{
		return $date->format($this->getLocalization()->getTimeFormatWithSeconds());
	}

	/**
	 * @param \DateTime $date
	 * @return string
	 */
	private function date_time(\DateTime $date)
	{
		return $date->format($this->getLocalization()->getDateTimeFormat());
	}

	/**
	 * @param \DateTime $date
	 * @return string
	 */
	private function date_time_pretty(\DateTime $date)
	{
		return \Sellastica\Utils\DateTime::prettify($date, $this->translator);
	}

	/**
	 * @param \DateTime $date
	 * @return string
	 */
	private function date_time_with_seconds(\DateTime $date)
	{
		return $date->format($this->getLocalization()->getDateTimeFormatWithSeconds());
	}

	/**
	 * @param float $number
	 * @param int $precision
	 * @return float
	 */
	private function round(float $number, int $precision)
	{
		return round($number, $precision);
	}
}