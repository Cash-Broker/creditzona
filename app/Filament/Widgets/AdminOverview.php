<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Blogs\BlogResource;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\Faqs\FaqResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Models\Blog;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\Lead;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected ?string $heading = 'Обзор';

    protected ?string $description = 'Бърз преглед на най-важното в административния панел.';

    protected function getStats(): array
    {
        $totalLeads = Lead::count();
        $newLeads = Lead::query()->where('status', 'new')->count();
        $messages = ContactMessage::count();
        $publishedBlogs = Blog::query()->where('is_published', true)->count();
        $publishedFaqs = Faq::query()->where('is_published', true)->count();

        return [
            Stat::make('Нови заявки', $newLeads)
                ->description("Общо {$totalLeads} заявки")
                ->icon(Heroicon::OutlinedClipboardDocumentList)
                ->color($newLeads > 0 ? 'warning' : 'gray')
                ->url(LeadResource::getUrl()),
            Stat::make('Контактни съобщения', $messages)
                ->description('Получени през сайта')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->color('info')
                ->url(ContactMessageResource::getUrl()),
            Stat::make('Публикувани статии', $publishedBlogs)
                ->description('Активни записи в блога')
                ->icon(Heroicon::OutlinedNewspaper)
                ->color('success')
                ->url(BlogResource::getUrl()),
            Stat::make('Публикувани ЧЗВ', $publishedFaqs)
                ->description('Активни въпроси и отговори')
                ->icon(Heroicon::OutlinedQuestionMarkCircle)
                ->color('primary')
                ->url(FaqResource::getUrl()),
        ];
    }
}
