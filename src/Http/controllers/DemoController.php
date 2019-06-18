<?php namespace Rvwoens\Gompie\Http\controllers;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Rvwoens\Gompie\Models\Naw;

/**
 * Class DemoController
 * @package Rvwoens\Gompie\Http\controllers
 * @version 1.0
 * @Author Ronald vanWoensel <rvw@cosninix.com>
 */
class DemoController extends Controller {

		public function index()
		{
			return view('gompie::contact');
		}

		public function sendMail(Request $request)
		{
			Naw::create($request->all());

			return redirect(route('contact'));
		}


	}