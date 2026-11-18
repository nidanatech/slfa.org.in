<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\MembershipController;
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
use App\Http\Controllers\Admin\AdminBroadcastController;
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

Route::get('/news', function () {
    $query = \App\Models\NewsPost::published();

    if (request('category')) {
        $query->where('category', request('category'));
    }

    return Inertia::render('SLFA/News', [
        'posts' => $query->paginate(12),
        'categories' => ['AGM', 'Get-Together', 'Medical Camp', 'Health Checkup'],
        'currentCategory' => request('category'),
    ]);
})->name('news');

Route::get('/news/{news}', function (\App\Models\NewsPost $news) {
    return Inertia::render('SLFA/NewsDetail', [
        'post' => $news,
    ]);
})->name('news.show');



Route::get('/contact', function () {
    return Inertia::render('SLFA/Contact');
})->name('contact');

Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery');
Route::get('/gallery/{album}', [GalleryController::class, 'show'])->name('gallery.show');

Route::get('/events', [EventController::class, 'index'])->name('events');
Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');

// Membership Application (public)
Route::get('/join', [MembershipController::class, 'create'])->name('join');
Route::post('/join', [MembershipController::class, 'store'])->name('join.store');

// ═══════════════════════════════════════════════════════════════════════════════
// AUTHENTICATED MEMBER ROUTES
// ═══════════════════════════════════════════════════════════════════════════════

Route::middleware('auth')->group(function () {
    // User Dashboard Feed
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Member Directory
    Route::get('/directory', [DirectoryController::class, 'index'])->name('directory');
    Route::get('/directory/{member}', [DirectoryController::class, 'show'])->name('directory.show');
    Route::post('/directory/{member}/message', [DirectoryController::class, 'message'])->name('directory.message');

    // RSVP for events
    Route::post('/events/{event}/rsvp', [EventController::class, 'rsvp'])->name('events.rsvp');

    // Documents library
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

    // Payments
    Route::get('/pay/{purpose}/{member}', [PaymentController::class, 'show'])->name('payment.show');
    Route::post('/pay/create-order', [PaymentController::class, 'createOrder'])->name('payment.create-order');
    Route::post('/pay/verify', [PaymentController::class, 'verify'])->name('payment.verify');
});

// ═══════════════════════════════════════════════════════════════════════════════
// ADMIN ROUTES
// ═══════════════════════════════════════════════════════════════════════════════

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
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
    Route::put('/members/{member}', [AdminMemberController::class, 'update'])->name('members.update');
    Route::patch('/members/{member}/approve', [AdminMemberController::class, 'approve'])->name('members.approve');
    Route::patch('/members/{member}/reject', [AdminMemberController::class, 'reject'])->name('members.reject');
    Route::patch('/members/{member}/toggle-featured', [AdminMemberController::class, 'toggleFeatured'])->name('members.toggle-featured');
    Route::delete('/members/{member}', [AdminMemberController::class, 'destroy'])->name('members.destroy');

    // News
    Route::resource('news', AdminNewsController::class);

    // Events
    Route::resource('events', AdminEventController::class);
    Route::get('/events/{event}/attendees', [AdminEventController::class, 'attendees'])->name('events.attendees');

    // Gallery
    Route::resource('albums', AdminGalleryController::class);
    Route::post('/albums/{album}/upload', [AdminGalleryController::class, 'uploadMedia'])->name('albums.upload');
    Route::delete('/media/{media}', [AdminGalleryController::class, 'destroyMedia'])->name('media.destroy');

    // Committee
    Route::resource('committee', AdminCommitteeController::class);
    Route::post('/committee/reorder', [AdminCommitteeController::class, 'reorder'])->name('committee.reorder');

    // Documents
    Route::resource('documents', AdminDocumentController::class);

    // Payments (read-only log)
    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');

    // Contact Messages
    Route::get('/messages', [AdminContactMessageController::class, 'index'])->name('messages.index');
    Route::patch('/messages/{message}/read', [AdminContactMessageController::class, 'markRead'])->name('messages.read');
    Route::delete('/messages/{message}', [AdminContactMessageController::class, 'destroy'])->name('messages.destroy');

    // Broadcast
    Route::get('/broadcast', [AdminBroadcastController::class, 'create'])->name('broadcast.create');
    Route::post('/broadcast', [AdminBroadcastController::class, 'send'])->name('broadcast.send');
});

// Dummy Payment Gateway
Route::get('/payment-gateway', function () {
    return Inertia::render('Auth/PaymentGateway');
})->name('payment.gateway');

Route::post('/payment-gateway/process', function () {
    return redirect()->route('dashboard')->with('success', 'Payment successful, membership activated.');
})->name('payment.process');

require __DIR__.'/auth.php';
