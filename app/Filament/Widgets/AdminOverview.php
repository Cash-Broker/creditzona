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
use App\Models\User;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class AdminOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected ?string $heading = 'Обзор';

    protected ?string $description = 'Бърз преглед на най-важното в административния панел.';

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $leadQuery = $this->getVisibleLeadsQuery($user);
        $totalLeads = (clone $leadQuery)->count();
        $newLeads = (clone $leadQuery)
            ->where('status', 'new')
            ->count();

        $leadStat = Stat::make($user->isAdmin() ? 'Нови заявки' : 'Моите заявки', $newLeads)
            ->description($user->isAdmin() ? "Общо {$totalLeads} заявки" : "Общо {$totalLeads} ваши заявки")
            ->icon(Heroicon::OutlinedClipboardDocumentList)
            ->color($newLeads > 0 ? 'warning' : 'gray')
            ->url(LeadResource::getUrl());

        if (! $user->isAdmin()) {
            return [$leadStat];
        }

        $messages = ContactMessage::count();
        $publishedBlogs = Blog::query()->where('is_published', true)->count();
        $publishedFaqs = Faq::query()->where('is_published', true)->count();

        return [
            $leadStat,
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

    private function getVisibleLeadsQuery(User $user): Builder
    {
        $query = Lead::query();

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($user): void {
            $builder
                ->where('assigned_user_id', $user->id)
                ->orWhere('additional_user_id', $user->id);
        });
    }
}
