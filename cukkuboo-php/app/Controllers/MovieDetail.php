<?php
 
namespace App\Controllers;
use CodeIgniter\RESTful\ResourceController;
use App\Helpers\AuthHelper; 
use App\Models\MovieDetailsModel;
use App\Models\UserModel;
use App\Models\CoreModel;
use App\Models\UsersubModel;
use App\Models\SubscriptionPlanModel;
use App\Models\NotificationModel;
use App\Libraries\Jwt;
use App\Libraries\AuthService;
 
 
class MovieDetail extends ResourceController
{
 
 
    public function __construct()
    {
        $this->session = \Config\Services::session();
        $this->input = \Config\Services::request();
        $this->moviedetail = new MovieDetailsModel();
        $this->subscriptionPlanModel = new SubscriptionPlanModel();
        $this->notificationModel = new NotificationModel();
        $this->coreModel = new CoreModel();
        $this->userModel = new UserModel();
        $this->usersubModel = new UsersubModel();
        $this->authService = new AuthService();
        $this->db = \Config\Database::connect();
    }
 
 
 
    public function store()
    {
        // $authHeader = $this->request->getHeaderLine('Authorization');
        $authHeader = AuthHelper::getAuthorizationToken($this->request);
        $user = $this->authService->getAuthenticatedUser($authHeader);
        if(!$user)
            return $this->failUnauthorized('Invalid or missing token.');
 
        $data = $this->request->getJSON(true);
 
        $movie_id = $data['mov_id'] ?? null;
 
        $moviedata = [
 
            'video' => $data['video'] ?? null,
            'title' => $data['title'] ?? null,
            'genre' => $data['genre'] ?? null,
            'description' => $data['description'] ?? null,
            'cast_details' => $data['cast_details'] ?? null,
            'category' => $data['category'] ?? null,
            'release_date' => $data['release_date'] ?? null,
            'age_rating' => $data['age_rating'] ?? null,
            'access' => $data['access'] ?? null,
            'status' => $data['status'] ?? null,
            'thumbnail' => $data['thumbnail'] ?? null,
            'trailer' => $data['trailer'] ?? null,
            'banner' => $data['banner'] ?? null,
            'duration' => $data['duration'] ?? null,
            'rating' => $data['rating'] ?? null,
            'modify_on' => date('Y-m-d H:i:s'),
        ];
        if (empty($movie_id)) {
            $moviedata['created_by'] =$data['created_by'] ?? null;
            $moviedata['created_on'] = date('Y-m-d H:i:s');
 
            if ($this->moviedetail->addMovie($moviedata)) {
                return $this->respond([
                    'success' => true,
                    'message' => 'Movie added successfully',
                    'data' => $moviedata
                ]);
            } else {
                return $this->failServerError('Failed to add movie');
            }
        } else {
 
            if ($this->moviedetail->updateMovie($movie_id, $moviedata)) {
                return $this->respond([
                    'success' => true,
                    'message' => 'Movie updated successfully',
                    'data' => $moviedata
                ]);
            } else {
                return $this->failServerError('Failed to update movie');
            }
        }
    }
 
public function getAllMovieDetails()
{
    $pageIndex = $this->request->getGet('pageIndex');
    $pageSize  = $this->request->getGet('pageSize');
    $search    = strtolower(trim($this->request->getGet('search'))); 
    
    if ($search === '0') {
        return $this->response->setJSON([
            'success' => true,
            'message' => 'No results found for the search term.',
            'data'    => [],
            'total'   => 0
        ]);
    }

    $builder = $this->moviedetail->builder(); 
    $builder->where('status !=', 9); 
    if ($search !== '') {
        $searchWildcard = '%' . str_replace(' ', '%', $search) . '%';
        if ($search === 'free') {
            $builder->where('access', 1);
        } elseif ($search === 'premium') {
            $builder->where('access', 2);
        } else {
            $searchWildcard = '%' . str_replace(' ', '%', $search) . '%';
            $builder->groupStart()
                ->like('LOWER(title)', $searchWildcard)
                ->orLike('LOWER(cast_details)', $searchWildcard)
                ->groupEnd();
        }
    }

    if (!is_numeric($pageIndex) || !is_numeric($pageSize) || $pageIndex < 0 || $pageSize <= 0) {
        $movies = $builder->orderBy('created_on', 'DESC')->get()->getResult();
    foreach ($movies as $movie) {
    $likes = $movie->likes ?? 0;
    $dislikes = $movie->dislikes ?? 0;
    $total = $likes + $dislikes;
    $movie->rating = $total > 0 ? round(($likes / $total) * 100, 2) : 0;
}
        return $this->response->setJSON([
            'success' => true,
            'message' => 'All movies fetched successfully.',
            'data'    => $movies,
            'total'   => count($movies)
        ]);
    }

    $pageIndex = (int)$pageIndex;
    $pageSize  = (int)$pageSize;
    $offset    = $pageIndex * $pageSize;
    $countBuilder = clone $builder;
    $total = $countBuilder->countAllResults(false);
    if ($search !== '' && $total === 0) {
        return $this->response->setJSON([
            'success' => true,
            'message' => 'No results found for the search term.',
            'data'    => [],
            'total'   => 0
        ]);
    }
    $movies = $builder
        ->orderBy('created_on', 'DESC')
        ->get($pageSize, $offset)
        ->getResult();

    return $this->response->setJSON([
        'success' => true,
        'message' => 'Paginated movies fetched successfully.',
        'data'    => $movies,
        'total'   => $total
    ]);
}
public function getMovieDetails()
{
    $pageIndex = $this->request->getGet('pageIndex');
    $pageSize  = $this->request->getGet('pageSize');
    $search    = strtolower(trim($this->request->getGet('search'))); 
    
    if ($search === '0') {
        return $this->response->setJSON([
            'success' => true,
            'message' => 'No results found for the search term.',
            'data'    => [],
            'total'   => 0
        ]);
    }

    $builder = $this->moviedetail->builder(); 
    $builder->where('status', 1);
    if ($search !== '') {
        $searchWildcard = '%' . str_replace(' ', '%', $search) . '%';
        if ($search === 'free') {
            $builder->where('access', 1);
        } elseif ($search === 'premium') {
            $builder->where('access', 2);
        } else {
            $searchWildcard = '%' . str_replace(' ', '%', $search) . '%';
            $builder->groupStart()
                ->like('LOWER(title)', $searchWildcard)
                ->orLike('LOWER(cast_details)', $searchWildcard)
                ->groupEnd();
        }
    }

    if (!is_numeric($pageIndex) || !is_numeric($pageSize) || $pageIndex < 0 || $pageSize <= 0) {
        $movies = $builder->orderBy('created_on', 'DESC')->get()->getResult();
    foreach ($movies as $movie) {
    $likes = $movie->likes ?? 0;
    $dislikes = $movie->dislikes ?? 0;
    $total = $likes + $dislikes;
    $movie->rating = $total > 0 ? round(($likes / $total) * 100, 2) : 0;
}
        return $this->response->setJSON([
            'success' => true,
            'message' => 'All movies fetched successfully.',
            'data'    => $movies,
            'total'   => count($movies)
        ]);
    }

    $pageIndex = (int)$pageIndex;
    $pageSize  = (int)$pageSize;
    $offset    = $pageIndex * $pageSize;
    $countBuilder = clone $builder;
    $total = $countBuilder->countAllResults(false);
    if ($search !== '' && $total === 0) {
        return $this->response->setJSON([
            'success' => true,
            'message' => 'No results found for the search term.',
            'data'    => [],
            'total'   => 0
        ]);
    }
    $movies = $builder
        ->orderBy('created_on', 'DESC')
        ->get($pageSize, $offset)
        ->getResult();

    return $this->response->setJSON([
        'success' => true,
        'message' => 'Paginated movies fetched successfully.',
        'data'    => $movies,
        'total'   => $total
    ]);
}

public function getMovieById($id)
{
    // $authHeader = $this->request->getHeaderLine('Authorization');
    $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $user = $this->authService->getAuthenticatedUser($authHeader);
    $getmoviesdetails = $this->moviedetail->getMovieDetailsById($id);
 
    if (!$getmoviesdetails) {
        return $this->failNotFound('Movie not found.');
    }
    if($getmoviesdetails['video'])
        $getmoviesdetails['video'] = $this->coreModel->simpleEncrypt($getmoviesdetails['video'],'Abhijith123456789');
    // if($getmoviesdetails['trailer'])
    //     $getmoviesdetails['trailer'] = $this->coreModel->simpleEncrypt($getmoviesdetails['trailer'],'Abhijith123456789');
 
   
    if (!$user) {
         $getmoviesdetails['video'] = null;
    } else {
        $user_id = $user['user_id'];
 
     
       // $getmoviesdetails['is_in_watch_later'] = $this->moviedetail->isInWatchLater($user_id, $id);
       $isInWatchLater = $this->moviedetail->isInWatchLater($user_id, $id);
       $getmoviesdetails['is_in_watch_later'] = $isInWatchLater;
       $getmoviesdetails['watch_later_id'] = $isInWatchLater
       ? $this->moviedetail->getWatchLaterId($user_id, $id)
       : null;

        $getmoviesdetails['is_in_watch_history'] = $this->moviedetail->isInWatchHistory($user_id, $id);
        $isViewed = $this->db->table('movie_view')
        ->where('user_id', $user['user_id'])
        ->where('mov_id', $id)
        ->countAllResults() > 0;

         $getmoviesdetails['is_viewed'] = $isViewed;


        $reaction = $this->db->table('movie_reactions')
        ->select('status')
        ->where('user_id', $user_id)
        ->where('mov_id', $id)
        ->get()
        ->getRow();

    $isLiked = false;
    $isDisliked = false;

    // if ($reaction) {
    //     if ($reaction->status == 1) {
    //         $isLiked = true;
    //     } elseif ($reaction->status == 2) {
    //         $isDisliked = true;
    //     }
    // }
    if ($reaction && isset($reaction->status)) {
    if ($reaction->status == 1) {
        $isLiked = true;
    } elseif ($reaction->status == 2) {
        $isDisliked = true;
    }
}


    // Add flags to movie array
    $getmoviesdetails['is_liked_by_user'] = $isLiked;         
    $getmoviesdetails['is_disliked_by_user'] = $isDisliked;   


       
        if (
            strtolower($user['subscription']) === "free" &&
            strtolower($user['user_type']) === "customer" &&
            isset($getmoviesdetails['access']) &&
            $getmoviesdetails['access'] != 1 // 1 = free
        ) {
            $getmoviesdetails['video'] = null;
        }
    }
 
        $likes = (int) ($getmoviesdetails['likes'] ?? 0);
        $dislikes = (int) ($getmoviesdetails['dislikes'] ?? 0);
        $totalReactions = $likes + $dislikes;

        $getmoviesdetails['rating'] = $totalReactions > 0 
        ? round(($likes / $totalReactions) * 100, 2)
        : 0;

        return $this->response->setJSON([
        'success' => true,
        'message' => 'Movie details fetched successfully.',
        'data'    => $getmoviesdetails
    ]);


    }
public function movieReaction($mov_id)
{
    // $authHeader = $this->request->getHeaderLine('Authorization');
    $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $user = $this->authService->getAuthenticatedUser($authHeader);
    if (!$user) return $this->failUnauthorized('Invalid or missing token.');
    $user_id = $user['user_id'];

    $data = $this->request->getJSON(true);

    if (!is_array($data) || !isset($data['status']) || !in_array($data['status'], [1, 2])) {
        return $this->fail('Missing or invalid "status". Use 1 for like, 2 for dislike.', 422);
    }

    $status = $data['status'];

    $existing = $this->db->table('movie_reactions')
        ->where('user_id', $user_id)
        ->where('mov_id', $mov_id)
        ->get()
        ->getRow();

    $movieTable = $this->db->table('movies_details');
    $reactionField = $status == 1 ? 'likes' : 'dislikes';
    $oppositeField = $status == 1 ? 'dislikes' : 'likes';

    if ($existing) {
        if ($existing->status == $status) {
            $this->db->table('movie_reactions')->delete(['reaction_id' => $existing->reaction_id]);

            $movieTable->set($reactionField, "$reactionField - 1", false)
                ->where('mov_id', $mov_id)->update();

            return $this->respond([
                'success' => true,
                'message' => ucfirst($reactionField) . ' removed.',
            ]);
        } else {
            $this->db->table('movie_reactions')
                ->where('reaction_id', $existing->reaction_id)
                ->update(['status' => $status]);

            $movieTable->set($reactionField, "$reactionField + 1", false)
                ->set($oppositeField, "$oppositeField - 1", false)
                ->where('mov_id', $mov_id)->update();

            return $this->respond([
                'success' => true,
                'message' => ucfirst($reactionField) . ' updated.',
            ]);
        }
    } else {
        $this->db->table('movie_reactions')->insert([
            'user_id' => $user_id,
            'mov_id' => $mov_id,
            'status' => $status,
        ]);

        $movieTable->set($reactionField, "$reactionField + 1", false)
            ->where('mov_id', $mov_id)->update();

        return $this->respond([
            'success' => true,
            'message' => ucfirst($reactionField) . ' added.',
        ]);
    }
}
 public function getMoviesList()
{
    $type = $this->request->getGet('type');
    $pageIndex = $this->request->getGet('pageIndex');
    $pageSize = $this->request->getGet('pageSize');
    $search = $this->request->getGet('search');

    // Validate and sanitize pagination params
    $pageIndex = is_numeric($pageIndex) && $pageIndex >= 0 ? (int)$pageIndex : 0;
    $pageSize = is_numeric($pageSize) && $pageSize > 0 ? (int)$pageSize : 10;

    $offset = $pageIndex * $pageSize;

    $builder = $this->db->table('movies_details')->where('status', 1);

    // Optional search
    // Optional search - strictly check only title, even if search is "0"
if ($this->request->getGet('search') !== null && trim($search) !== '') {
    $search = strtolower(trim($search));
    $searchWildcard = '%' . $search . '%';

    $builder->groupStart()
        ->like('LOWER(title)', $searchWildcard)
        ->groupEnd();
}


    // Sorting logic based on `type`
    switch ($type) {
        case 'trending':
            $builder->orderBy('likes', 'DESC');
            $title = 'Trending Movies';
            break;

        case 'latest':
            $builder->orderBy('release_date', 'DESC');
            $title = 'Latest Movies';
            break;

        case 'most_viewed':
            $builder->orderBy('views', 'DESC');
            $title = 'Most Watched Movies';
            break;

        default:
            return $this->respond([
                'success' => false,
                'message' => 'Invalid type parameter.'
            ], 400);
    }

    
    // Get total count
    $countBuilder = clone $builder;
    $total = $countBuilder->countAllResults(true);

    // Get paginated results
    $movies = $builder->get($pageSize, $offset)->getResultArray();

    // Format output
    $formattedData = array_map(function ($movie) {
    $likes = (int) ($movie['likes'] ?? 0);
    $dislikes = (int) ($movie['dislikes'] ?? 0);
    $total = $likes + $dislikes;

    $calculatedRating = $total > 0 ? round(($likes / $total) * 100, 2) : 0;

    return [
        'mov_id' => $movie['mov_id'],
        'title' => $movie['title'],
        'thumbnail' => $movie['thumbnail'],
        'banner' => $movie['banner'],
        'release_date' => $movie['release_date'],
        'views' => $movie['views'],
        'rating' => $calculatedRating,
        'description' => $movie['description'],
        'duration' => $movie['duration'],
    ];
}, $movies);

    // Calculate total pages safely
    $totalPages = ($pageSize > 0) ? ceil($total / $pageSize) : 0;

    return $this->respond([
        'success' => true,
        'type' => $type,
        'title' => $title,
        'pageIndex' => $pageIndex,
        'pageSize' => $pageSize,
        'total' => $total,
        'totalPages' => $totalPages,
        'data' => $formattedData
    ]);
}

 
    public function deleteMovieDetails($mov_id)
    {
        // $authHeader = $this->request->getHeaderLine('Authorization');
       $authHeader = AuthHelper::getAuthorizationToken($this->request);
        $user = $this->authService->getAuthenticatedUser($authHeader);
        if(!$user)
            return $this->failUnauthorized('Invalid or missing token.');
 
        $status = 9;
 
        // Call model method to update the status
        if ($this->moviedetail->deleteMovieDetailsById($status, $mov_id)) {
            return $this->respond([
                'success' => true,
                'message' => "Movie with ID $mov_id marked as deleted successfully.",
                'data' => []
            ]);
        } else {
            return $this->failServerError("Failed to delete movie with ID $mov_id.");
        }
    }
 
 
 
 
 
 
 
 
 
// --------------------------------- mobile--------------------------------------//
 
 
 
   public function homeDisplay()
{
    $movieModel = new MovieDetailsModel();
    
 
    $featuredRaw = $movieModel->getFeaturedMovies();
    $trendingRaw = $movieModel->getTrendingMovies();
 
    $featured = array_map([$this, 'formatMovie'], $featuredRaw);
    $trending = array_map([$this, 'formatMovie'], $trendingRaw);
 
    return $this->respond([
        'success' => true,
        'message'=>'success',
        'data' => [
            'list_1' => $featured,
            'list_2' => $trending,
        ]
    ]);
}
 
 
    private function formatMovie($movie)
{
    
    $likes = intval($movie['likes'] ?? 0);
    $dislikes = intval($movie['dislikes'] ?? 0);
    $total = $likes + $dislikes;


    return [
        'mov_id' => $movie['mov_id'],
        'title' => $movie['title'],
        'cast_details' => $movie['cast_details'],
        'category' => $movie['category'],
        'release_date' => $movie['release_date'],
        'age_rating' => $movie['age_rating'],
        'access' => $movie['access'],
        'trailer' => $movie['trailer'],
        'banner' => $movie['banner'],
        'thumbnail' => $movie['thumbnail'],
        'likes' => $likes,
        'dislikes' => $dislikes,
        'rating' => $total > 0 ? round(($likes / $total) * 100, 2) : 0,
        'duration' => $movie['duration'],
        'genre' => explode(',', $movie['genre']),
        'description' => $movie['description']
    ];
}

 
// public function getLatestMovies()
// {
//     $pageIndex = (int) $this->request->getGet('pageIndex') ?? 0;
//     $pageSize  = (int) $this->request->getGet('pageSize') ?? 10;
//     $search    = $this->request->getGet('search'); 
//     $offset    = $pageIndex * $pageSize;

//     $movieModel = new MovieDetailsModel();
//     $result = $movieModel->getLatestMovies($pageSize, $offset, $search);

//     $latest = array_map([$this, 'formatMovie'], $result['movies']);

//     return $this->response->setJSON([
//         'success' => true,
//         'message' => 'success',
//         'data'    => $latest,
//         'total'   => $result['total']
//     ]);
// }


 
public function mostWatchedMovies()
{
    $movieModel = new MovieDetailsModel();
    $moviesRaw = $movieModel->getMostWatchedMovies();
 
    $movies = array_map([$this, 'formatMovie'], $moviesRaw);
 
    return $this->response->setJSON([
        'success' => true,
        'message' => 'Top 10 most watched movies fetched successfully.',
        'data' => $movies
    ]);
}
 
 
 
// ---------------------------------Admin home  Display--------------------------------------//
 
public function latestMovies()
{
    // $authHeader = $this->request->getHeaderLine('Authorization');
     $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $user = $this->authService->getAuthenticatedUser($authHeader);

    if (!$user) {
        return $this->failUnauthorized('Invalid or missing token.');
    }
    $pageIndex = (int) $this->request->getGet('pageIndex') ?? 0;
    $pageSize = (int) $this->request->getGet('pageSize') ?? 10;
    $search    = $this->request->getGet('search');
    $offset = $pageIndex * $pageSize;

    $movieModel = new MovieDetailsModel();
    $result = $movieModel->latestAddedMovies($pageSize, $offset, $search);

    return $this->response->setJSON([
        'success' => true,
        'message' => 'Latest movies fetched successfully.',
        'data' => $result['movies'],
        'total' => $result['total']
    ]);
}


    public function getMostWatchMovies()
    {
        // $authHeader = $this->request->getHeaderLine('Authorization');
         $authHeader = AuthHelper::getAuthorizationToken($this->request);
        $user = $this->authService->getAuthenticatedUser($authHeader);
        if (!$user)
            return $this->failUnauthorized('Invalid or missing token.');
        $pageIndex = (int) $this->request->getGet('pageIndex') ?? 0;
        $pageSize = (int) $this->request->getGet('pageSize') ?? 10;
        $search    = $this->request->getGet('search');
        $offset = $pageIndex * $pageSize;
        
        $movieModel = new MovieDetailsModel();
        $result = $movieModel->getMostWatchMovies($pageSize, $offset, $search);
 
        return $this->response->setJSON([
            'success'  => true,
            'message' => 'Most watched movies fetched successfully.',
            'data' => $result
        ]);
    }
    public function countActiveMovies()
    {
        // $authHeader = $this->request->getHeaderLine('Authorization');
         $authHeader = AuthHelper::getAuthorizationToken($this->request);
        $user = $this->authService->getAuthenticatedUser($authHeader);
        if (!$user)
            return $this->failUnauthorized('Invalid or missing token.');
        $movieModel = new MovieDetailsModel();
        $activeCount = $movieModel->countActiveMovies();
 
        return $this->respond([
            'success' => true,
            'message'=>'success',
            'data' => $activeCount
        ]);
    }
    public function countInactiveMovie()
    {
        // $authHeader = $this->request->getHeaderLine('Authorization');
         $authHeader = AuthHelper::getAuthorizationToken($this->request);
        $user = $this->authService->getAuthenticatedUser($authHeader);
        if (!$user)
            return $this->failUnauthorized('Invalid or missing token.');
        $movieModel = new MovieDetailsModel();
        $inactiveCount = $movieModel->countInactiveMovies();
 
        return $this->respond([
            'success' => true,
            'message'=>'success',
            'data' => $inactiveCount
        ]);
    }

 
public function getUserHomeData()
{
    $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $user = $this->authService->getAuthenticatedUser($authHeader);
    $hasUnread = false;
    if ($user) {
        $userId = $user['user_id'];
        $notificationModel = new NotificationModel();
        $hasUnread = $notificationModel->hasUnreadNotifications($userId);
    }

    return $this->respond([
        'success' => true,
        'message' => true,
        'data' => [
            'active_movie_count' => $this->moviedetail->countActiveMovies(),
            'In_active_movie_count' => $this->moviedetail->countInactiveMovies(),
            'has_unread_notifications' => $hasUnread,

            'list_1' => [
                'type' => 'featured',
                'heading' => 'Featured Movies',
                'data' => array_map([$this, 'calculateRatingForMovie'], $this->moviedetail->getFeaturedMovies())
            ],
            'list_2' => [
                'type' => 'trending',
                'heading' => 'Trending Movies',
                'data' => array_map([$this, 'calculateRatingForMovie'], $this->moviedetail->getTrendingMovies())
            ],
            'list_3' => [
                'type' => 'latest',
                'heading' => 'Latest Movies',
                'data' => array_map([$this, 'calculateRatingForMovie'], $this->moviedetail->latestMovies())
            ],
            'list_4' => [
                'type' => 'most_viewed',
                'heading' => 'Most Watched Movies',
                'data' => array_map([$this, 'calculateRatingForMovie'], $this->moviedetail->getMostWatchedMovies())
            ]
        ]
    ]);
}




    public function getAdminDashBoardData()
    {
        // $authHeader = $this->request->getHeaderLine('Authorization');
         $authHeader = AuthHelper::getAuthorizationToken($this->request);
        $user = $this->authService->getAuthenticatedUser($authHeader);
        if (!$user)
            return $this->failUnauthorized('Invalid or missing token.');
       
        return $this->respond([
            'success' => true,
            'message' => true,
            'data' => [
                'active_user_count'=>$this->userModel->countActiveUsers(),
                'subscriber_count'=>$this->usersubModel->countCurrentMonthSubscribers(),
                'total_revenue'=>$this->usersubModel->currentTotalRevenue(),
                'transaction_list'=>$this->usersubModel->getTransactions(),
                'active_movie_count' => $this->moviedetail->countActiveMovies(),
                'In_active_movie_count' => $this->moviedetail->countInactiveMovies(),
                'latest_movies' =>$this->moviedetail->latestAddedMovies(),
                'most_watched_movies'=>$this->moviedetail->getMostWatchMovies()
            ]
        ]);
    }
// ------------------------------------Related Movies Display---------------------------
 
public function getRelatedMovies($id)
{
    // $authHeader = $this->request->getHeaderLine('Authorization');
     $authHeader = AuthHelper::getAuthorizationToken($this->request);
    $user = $this->authService->getAuthenticatedUser($authHeader);
 
    if (!$user) {
        return $this->failUnauthorized('Invalid or missing token.');
    }
 
    $currentMovie = $this->moviedetail->find($id);
    if (!$currentMovie) {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Movie not found.',
            'data' => []
        ]);
    }
 
 
    $pageIndex = (int) $this->request->getGet('pageIndex', FILTER_VALIDATE_INT);
    $pageSize  = (int) $this->request->getGet('pageSize', FILTER_VALIDATE_INT);
    $offset = $pageIndex * $pageSize;
 
    $queryBuilder = $this->moviedetail->getRelatedMoviesQuery($currentMovie, $id);
 
    $relatedMovies = $queryBuilder
                        ->orderBy('mov_id', 'DESC')
                        ->get($pageSize, $offset)
                        ->getResultArray();
 
    return $this->response->setJSON([
        'success' => true,
        'message' => 'Related movies fetched successfully.',
        'data' => $relatedMovies
    ]);
}
 private function calculateRatingForMovie($movie)
{
    $likes = intval($movie['likes'] ?? 0);
    $dislikes = intval($movie['dislikes'] ?? 0);
    $total = $likes + $dislikes;
    $movie['rating'] = $total > 0 ? round(($likes / $total) * 100, 1) : 0;
    return $movie;
}
}
 
 