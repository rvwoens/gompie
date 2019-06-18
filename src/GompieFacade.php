<?php namespace Rvwoens\Gompie;
use Illuminate\Support\Facades\Facade;

/**
 * Class GompieFacade
 * @package Rvwoens\Gompie
 * @version 1.0
 * @Author Ronald vanWoensel <rvw@cosninix.com>
 */
class GompieFacade extends Facade {
	protected static function getFacadeAccessor() { return 'gompie'; }
}
