<?php namespace Tumblet\Http\Controllers;

use Tumblr\API\RequestException;
use Tumblet\Exceptions\EmptyTumbletException;
use Tumblet\Exceptions\PageOutOfRangeException;

use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\View;
use Tumblet\Http\Requests;
use Tumblet\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Tumblet\Tumblet\TumbletRepository;
use Tumblet\Tumblet\TumbletPostRepository;

class TumbletController extends Controller {

	private $tumbletRepository;
	private $tumbletPostRepository;

	public function __construct (TumbletRepository $tumbletRepository, TumbletPostRepository $tumbletPostRepository)
	{
	    $this->tumbletRepository = $tumbletRepository;
		$this->tumbletPostRepository = $tumbletPostRepository;
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function show($tumblrName)
	{

        $currentPage = Input::get('page');

        if(is_null($currentPage)) {
            $currentPage = "1";
        }

        try {
            $tumblet = $this->tumbletRepository->getByName($tumblrName);
            $posts = $this->tumbletPostRepository->getPostsForTumbletAndPage($tumblet, $currentPage);
        }
        catch (RequestException $e) {
            Session::flash('error', 'That is not a valid Tumblr blog name');
            return redirect('/');
        }
        catch (EmptyTumbletException $e) {
            Session::flash('error', $e->getMessage());
            return redirect('/');
        }
        catch (PageOutOfRangeException $e) {
            Session::flash('error', $e->getMessage());
            return redirect("/{$tumblrName}?page=1");
        }

        // since our collection of posts only contains the visible posts,
        // lets paginate based on the tumblet's attributes instead
        $paginator = new Paginator(range(1, $tumblet->postTotal), $tumblet->postTotal, $tumblet->postsPerPage, $currentPage);

        $paginator->setPath("");
		return View::make('tumblet.show')
			->with('tumblet', $tumblet)
			->with('posts', $posts)
			->with('pages', $paginator);
	}
	
	public function storeAndRedirect ()
	{
		$tumblrname = Input::get('tumblrname');

		return Redirect::to("{$tumblrname}");
	}
}
