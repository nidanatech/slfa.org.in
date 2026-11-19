<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminMemberController;
use App\Http\Controllers\Admin\AdminNewsController;
use App\Http\Controllers\Admin\AdminEventController;
use App\Http\Controllers\Admin\AdminGalleryController;
use App\Http\Controllers\Admin\AdminCommitteeController;
use App\Http\Controllers\Admin\AdminDocumentController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminContactMessageController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ═══════════════════════════════════════════════════════════════════════════════
// PUBLIC PAGES
// ═══════════════════════════════════════════════════════════════════════════════

Route::get('/', function () {
    return Inertia::render('SLFA/Home', [
        'latestNews' => \App\Models\NewsPost::published()->take(3)->get(),
        'upcomingEvents' => \App\Models\Event::upcoming()->take(3)->get(),
        'leadershipSetup' => \App\Models\CommitteeMember::where('type', 'Leadership')
            ->where('show_in_website', true)
            ->orderBy('display_order')->get(),
        'executiveCommittee' => \App\Models\CommitteeMember::where('type', 'Executive')
            ->where('show_in_website', true)
            ->orderBy('display_order')->get(),
    ]);
})->name('home');

Route::get('/about', function () {
    return Inertia::render('SLFA/About', [
        'leadershipSetup' => \App\Models\CommitteeMember::where('type', 'Leadership')
            ->where('show_in_website', true)
            ->orderBy('display_order')->get(),
        'executiveCommittee' => \App\Models\CommitteeMember::where('type', 'Executive')
            ->where('show_in_website', true)
            ->orderBy('display_order')->get(),
    ]);
})->name('about');

Route::get('/happenings', function () {
    return Inertia::render('SLFA/Happenings', [
        'posts' => \App\Models\NewsPost::published()->paginate(9),
        'upcomingEvents' => \App\Models\Event::upcoming()->get(),
        'pastEvents' => \App\Models\Event::past()->take(6)->get(),
    ]);
})->name('happenings');

Route::get('/news', fn () => redirect()->to(route('happenings').'#news', 301))->name('news');

Route::get('/news/{legacyNews}', function (int $legacyNews) {
    $post = \App\Models\NewsPost::findOrFail($legacyNews);
    return redirect()->route('news.show', $post->public_id, 301);
})->whereNumber('legacyNews');

Route::get('/news/{news:public_id}', function (\App\Models\NewsPost $news) {
    abort_unless($news->published_at && $news->published_at->lte(now()), 404);
    return Inertia::render('SLFA/NewsDetail', [
        'post' => $news->load('photos'),
    ]);
})->whereUlid('news')->name('news.show');

Route::get('/sitemap.xml', function () {
    $urls = collect(['home', 'about', 'happenings', 'contact', 'membership'])
        ->map(fn ($name) => route($name))
        ->concat(\App\Models\NewsPost::published()->pluck('public_id')->map(fn ($id) => route('news.show', $id)))
        ->concat(\App\Models\Event::pluck('public_id')->map(fn ($id) => route('events.show', $id)));

    return response()->view('sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
})->name('sitemap');



Route::get('/contact', function () {
    return Inertia::render('SLFA/Contact');
})->name('contact');

Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('contact.store');

Route::get('/gallery', fn () => redirect()->to(route('happenings').'#news', 301))->name('gallery');
Route::get('/gallery/{album}', [GalleryController::class, 'show'])->name('gallery.show');

Route::get('/events', fn () => redirect()->to(route('happenings').'#events', 301))->name('events');
Route::get('/events/{legacyEvent}', function (int $legacyEvent) {
    $event = \App\Models\Event::findOrFail($legacyEvent);
    return redirect()->route('events.show', $event->public_id, 301);
})->whereNumber('legacyEvent');
Route::get('/events/{event:public_id}', [EventController::class, 'show'])->whereUlid('event')->name('events.show');
Route::post('/events/{event:public_id}/admission', [EventController::class, 'admission'])->whereUlid('event')->middleware('throttle:5,1')->name('events.admission');

// Membership Application (public)
Route::redirect('/join', '/register')->name('join');
Route::get('/membership', fn () => Inertia::render('SLFA/Membership'))->name('membership');

// ═══════════════════════════════════════════════════════════════════════════════
// AUTHENTICATED MEMBER ROUTES
// ═══════════════════════════════════════════════════════════════════════════════

Route::middleware('auth')->group(function () {
    // User Dashboard Feed
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/membership', [ProfileController::class, 'updateMembership'])->name('profile.membership.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Member Directory
    Route::middleware('can:access-directory')->group(function () {
        Route::get('/directory', [DirectoryController::class, 'index'])->name('directory');
        Route::get('/directory/{member}', [DirectoryController::class, 'show'])->name('directory.show');
        Route::post('/directory/{member}/message', [DirectoryController::class, 'message'])->name('directory.message');
    });

    // RSVP for events
    Route::post('/events/{event:public_id}/rsvp', [EventController::class, 'rsvp'])->whereUlid('event')->name('events.rsvp');

    // Documents library
    Route::middleware('can:access-directory')->group(function () {
        Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
        Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    });

    // Payments
    Route::get('/pay/{purpose}/{member}', [PaymentController::class, 'show'])->name('payment.show');
    Route::post('/pay/create-order', [PaymentController::class, 'createOrder'])->name('payment.create-order');
    Route::post('/pay/verify', [PaymentController::class, 'verify'])->name('payment.verify');
});

// ═══════════════════════════════════════════════════════════════════════════════
// ADMIN ROUTES
// ═══════════════════════════════════════════════════════════════════════════════

Route::middleware(['auth', 'can:access-admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Admins
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    // Members
    Route::get('/members', [AdminMemberController::class, 'index'])->name('members.index');
    Route::get('/members/create', [AdminMemberController::class, 'create'])->name('members.create');
    Route::post('/members', [AdminMemberController::class, 'store'])->name('members.store');
    Route::get('/members/{member}', [AdminMemberController::class, 'show'])->name('members.show');
    Route::get('/members/{member}/id-proof', [AdminMemberController::class, 'downloadIdProof'])->name('members.id-proof');
    Route::put('/members/{member}', [AdminMemberController::class, 'update'])->name('members.update');
    Route::patch('/members/{member}/approve', [AdminMemberController::class, 'approve'])->name('members.approve');
    Route::patch('/members/{member}/reject', [AdminMemberController::class, 'reject'])->name('members.reject');
    Route::patch('/members/{member}/toggle-featured', [AdminMemberController::class, 'toggleFeatured'])->name('members.toggle-featured');
    Route::delete('/members/{member}', [AdminMemberController::class, 'destroy'])->name('members.destroy');

    // News
    Route::resource('news', AdminNewsController::class)->except(['show']);
    Route::delete('/news/{news}/photos/{photo}', [AdminNewsController::class, 'destroyPhoto'])->name('news.photos.destroy');

    // Events
    Route::resource('events', AdminEventController::class)->except(['show']);
    Route::get('/events/{event}/attendees', [AdminEventController::class, 'attendees'])->name('events.attendees');
    Route::patch('/events/{event}/admissions/{admission}', [AdminEventController::class, 'updateAdmission'])->name('events.admissions.update');

    // Gallery
    Route::resource('albums', AdminGalleryController::class);
    Route::post('/albums/{album}/upload', [AdminGalleryController::class, 'uploadMedia'])->name('albums.upload');
    Route::patch('/media/{media}', [AdminGalleryController::class, 'updateMedia'])->name('media.update');
    Route::delete('/media/{media}', [AdminGalleryController::class, 'destroyMedia'])->name('media.destroy');

    // Committee
    Route::resource('committee', AdminCommitteeController::class)->except(['show']);
    Route::post('/committee/reorder', [AdminCommitteeController::class, 'reorder'])->name('committee.reorder');

    // Documents
    Route::resource('documents', AdminDocumentController::class)->only(['index', 'create', 'store', 'destroy']);

    // Payments (read-only log)
    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');

    // Contact Messages
    Route::get('/messages', [AdminContactMessageController::class, 'index'])->name('messages.index');
    Route::patch('/messages/{message}/read', [AdminContactMessageController::class, 'markRead'])->name('messages.read');
    Route::delete('/messages/{message}', [AdminContactMessageController::class, 'destroy'])->name('messages.destroy');

});

require __DIR__.'/auth.php';
