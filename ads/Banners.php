<?php
/**
 * Created with love by Dudarev Ilya.
 * Date: 23.12.14
 * Time: 15:08
 */

/**
 * Расширение-виджет для отображение рекламных баннеров.
 *
 * Usecase:
 * ...
 * Yii::import('ext.ads.Banners');
 * ?>
 *
 * <?= Banners::get('superbanner'); ?> // Вернет несколько, один или не одного баннера.
 * ...
 *
 * По умолчанию, будет возвращено столько баннеров, сколько указано в config.php, но не более, чем указано во [views]remains.
 *
 * Можно указать точное кол-во:
 * ...
 * <?= Banners::get('superbanner', 10); ?>
 * ...
 *
 * Или в процентном отношении, относително [views]remains:
 * ...
 * <?= Banners::get('superbanner', '10%'); ?>
 * ...
 *
 *
 * @author Dudarev Ilya <ilya@txtup.ru>
 */
class Banners extends \CWidget
{
    /**
     * Название контроллера, одноименно с названием папки
     *
     * @var string
     */
    public $name;

    /**
     * Настройки по умолчанию
     *
     * @var array
     */
    public $config = [
        'href' => '/копеечка_в_копилку.html',
        'views' => [
            'remains' => 50,
            'max' => 50
        ],
        'defaultCount' => 1
    ];

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Возвращает путь до папки с баннерами
     *
     * @return string
     */
    private function getViewsPath()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'views';
    }

    /**
     * Возвращает результат проверки: существует ли папка баннера?
     *
     * @return bool
     */
    private function bannerExist()
    {
        return is_dir($this->getViewsPath() . DIRECTORY_SEPARATOR . $this->name);
    }

    /**
     * Возвращает результат проверки: существует ли файл баннера?
     *
     * @param $filename
     * @return bool
     */
    private function fileExist($filename)
    {
        return file_exists($this->getViewsPath() . DIRECTORY_SEPARATOR . $this->name . DIRECTORY_SEPARATOR . $filename);
    }

    /**
     * Инициализируем настройки
     */
    private function initConfig()
    {
        $config = require_once($this->getViewsPath() . DIRECTORY_SEPARATOR . $this->name . DIRECTORY_SEPARATOR . 'config.php');
        $this->config = array_merge(
            $this->config,
            $config
        );
    }

    /**
     * Возвращает количество повторений
     *
     * @param $count
     * @return int
     */
    public function getRepeatCount($count)
    {
        if ($this->config['views']['remains'] <= 0)
            $count = 0;

        if (!isset($count) || empty($count))
            $count = $this->config['defaultCount'];

        if (is_string($count) && strpos($count, '%') !== false)
        {
            $count = intval($count);
            $count = ceil($this->config['views']['remains'] * $count / 100);
        }
        else
            $count = intval($count);

        return min($count, $this->config['views']['remains']);
    }

    /**
     * Возвращает html для нескольких баннеров или пустую строку
     *
     * @param $count
     * @return string
     */
    public function renderSeveral($count)
    {
        $count = $this->getRepeatCount($count);

        $html = '';

        if ($count > 0)
        {
            for ($i = 0; $i < $count; $i++)
                $html .= $this->renderOne();

            $this->save();
        }

        return $html;
    }

    /**
     * Возвращает рендер файла обернутый в ссылку
     * Ссылка имеет следующий идентификатор: banners-<banner_name>-<remains_count>
     *
     * @return string
     */
    public function renderOne()
    {
        $this->config['views']['remains']--;

        return \CHtml::link($this->render('/' . $this->name . '/html', [], true), $this->config['href'], ['target' => '_blank', 'id' => "banners-{$this->name}-" . $this->config['views']['remains']]);
    }

    public function save()
    {
        $fh = fopen($this->getViewsPath() . DIRECTORY_SEPARATOR . $this->name . DIRECTORY_SEPARATOR . 'config.php', 'w');
        fwrite($fh, "<?php return " . var_export($this->config, true) . ";");
        fclose($fh);
    }

    public static function get($name, $count = null)
    {
        $banner = new self($name);

        if (!$banner->bannerExist())
        {
            return null;
        }

        if (!$banner->fileExist('html.php') || !$banner->fileExist('config.php'))
            return null;

        $banner->initConfig();

        return $banner->renderSeveral($count);
    }

} 