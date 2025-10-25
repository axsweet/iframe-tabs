<?php

namespace Ichynul\IframeTabs\Http\Controllers;

use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Ichynul\IframeTabs\IframeTabs;
use Illuminate\Routing\Controller;
use \Encore\Admin\Widgets\Navbar;

class IframeTabsController extends Controller
{
    // ... existing code ...
    public function index(Content $content)
    {
        if (!IframeTabs::boot()) {
            return redirect(admin_base_path('dashboard'));
        }

        $openUrl = request()->query('openUrl');
        $openTitle = request()->query('openTitle', 'Requested Page');

        if (!$openUrl) {
            $tab = session()->pull('admin_pending_tab');
            if ($tab) {
                $openUrl = $tab['url'] ?? null;
                $openTitle = $tab['title'] ?? 'Requested Page';
            }
        }

        // If provided, open the URL in a real new tab, but do NOT change the home tab.
        if ($openUrl) {
            \Encore\Admin\Admin::script("
                (function(){
                    var u = " . json_encode($openUrl) . ";
                    if (u) { try { window.open(u, '_blank'); } catch(e) {} }
                })();
            ");
        }

        // Always keep default home tab
        $homeUri = admin_base_path('dashboard');
        $homeTitle = IframeTabs::config('home_title', 'Index');
        $homeIcon = IframeTabs::config('home_icon', 'fa-home');

        $items = [
            'header' => '',
            'trans' => [
                'operations' => trans('admin.iframe_tabs.operations'),
                'refresh_current' => trans('admin.iframe_tabs.refresh_current'),
                'close_current' => trans('admin.iframe_tabs.close_current'),
                'close_all' => trans('admin.iframe_tabs.close_all'),
                'close_other' => trans('admin.iframe_tabs.close_other'),
                'open_in_new' => trans('admin.iframe_tabs.open_in_new'),
                'open_in_pop' => trans('admin.iframe_tabs.open_in_pop'),
                'scroll_left' => trans('admin.iframe_tabs.scroll_left'),
                'scroll_right' => trans('admin.iframe_tabs.scroll_right'),
                'scroll_current' => trans('admin.iframe_tabs.scroll_current'),
                'refresh_succeeded' => trans('admin.refresh_succeeded'),
            ],
            'home_uri' => $homeUri,
            'home_title' => $homeTitle,
            'home_icon' => $homeIcon,
            'use_icon' => IframeTabs::config('use_icon', true) ? '1' : '',
            'pass_urls' => implode(',', IframeTabs::config('pass_urls', ['/auth/logout'])),
            'iframes_index' => admin_url(),
            'tabs_left' => IframeTabs::config('tabs_left', '42'),
            'bind_urls' => IframeTabs::config('bind_urls', 'none'),
            'bind_selector' => IframeTabs::config('bind_selector', '.box-body table.table tbody a.grid-row-view,.box-body table.table tbody a.grid-row-edit,.box-header .pull-right .btn-success'),
        ];


        \View::share($items);

        Admin::navbar(function (Navbar $navbar) {
            $navbar->left(view('iframe-tabs::ext.tabs'));
            $navbar->right(view('iframe-tabs::ext.options'));
        });

        return $content;
    }

    protected function isSameOrigin(string $url): bool
    {
        $parsed = parse_url($url);
        $app = parse_url(config('app.url'));
        if (!isset($parsed['host']) || !isset($app['host'])) {
            return false;
        }
        $scheme = $parsed['scheme'] ?? 'https';

        return strcasecmp($parsed['host'], $app['host']) === 0
            && in_array($scheme, ['http', 'https'], true);
    }

    public function dashboard(Content $content)
    {
        return $content
            ->header('Default page')
            ->description('Default page')
            ->body('Default page have not set ,pleace edit config in `config/admin.php`'
                . "<pre>'extensions' => [
                'iframe-tabs' => [
                    'enable' => true,
                    'home_action' => App\Admin\Controllers\HomeController::class . '@index',
                    'home_title' => 'Home',
                    'home_icon' => 'fa-home',
                    'use_icon' => true,
                    'tabs_css' =>'vendor/laravel-admin-ext/iframe-tabs/dashboard.css',
                    'layer_path' => 'vendor/laravel-admin-ext/iframe-tabs/layer/layer.js',
                    'pass_urls' => ['/admin/auth/logout', '/admin/auth/lock'],
                    'force_login_in_top' => true,
                    'tabs_left'  => 42,
                    'bind_urls' => 'popup',
                    'bind_selector' => '.box-body table.table tbody a.grid-row-view,.box-body table.table tbody a.grid-row-edit,.box-header .pull-right .btn-success,.popup',
                ]
            ],</pre>");
    }
}