<?php
namespace capoloja\webshell\controllers;

use Yii;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\Response;

/**
 * DefaultController
 *
 * @author Alexander Makarov <sam@rmcreative.ru>
 *
 * @property \capoloja\webshell\Module $module
 */
class DefaultController extends Controller
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        Yii::$app->request->enableCsrfValidation = false;
        parent::init();
    }

    /**
     * Displays initial HTML markup
     * @return string
     */
    public function actionIndex()
    {
        $this->layout = 'shell';
        return $this->render('index', [
            'quitUrl' => $this->module->quitUrl ? Url::toRoute($this->module->quitUrl) : null,
            'greetings' => $this->module->greetings
        ]);
    }

    /**
     * RPC handler
     * @return array
     */
    public function actionRpc()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $options = Json::decode(Yii::$app->request->getRawBody());
		//print var_export($options, true);
		Yii::trace('pf: '.var_export($options, true));
        switch ($options['method']) {
            case 'yii':
                list ($status, $output) = $this->runConsole(implode(' ', $options['params']));
                return ['result' => $output];
				break;
            case 'svn':
                list ($status, $output) = $this->runSvn(implode(' ', $options['params']));
                return ['result' => $output];
				break;
        }
    }

    /**
     * Runs console command
     *
     * @param string $command
     *
     * @return array [status, output]
     */
    private function runConsole($command)
    {
        $cmd = Yii::getAlias($this->module->yiiScript) . ' ' . $command . ' 2>&1';

		Yii::trace('pf runConsole: '.$cmd);
        $handler = popen($cmd, 'r');
        $output = '';
        while (!feof($handler)) {
			Yii::trace("pf runConsole getting output: [".var_export($output, true)."]");
            $output .= fgets($handler);
        }

        $output = trim($output);
        $status = pclose($handler);

		Yii::trace("pf runConsole status: $status, output [".var_export($output, true)."]");
        return [$status, $output];
    }
	
    /**
     * Runs console command
     *
     * @param string $command
     *
     * @return array [status, output]
     */
    private function runSvn($command)
    {
        $cmd = 'svn ' . $command . ' 2>&1';

		Yii::trace('pf runConsole: '.$cmd);
        $handler = popen($cmd, 'r');
        $output = '';
        while (!feof($handler)) {
			Yii::trace("pf runConsole getting output: [".var_export($output, true)."]");
            $output .= fgets($handler);
        }

        $output = trim($output);
        //$output = $cmd;
        $status = pclose($handler);

		Yii::trace("pf runConsole status: $status, output [".var_export($output, true)."]");
        return [$status, $output];
    }
}