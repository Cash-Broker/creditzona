<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Blogs\BlogResource;
use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\Faqs\FaqResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\ReturnedToMeLeads\ReturnedToMeLeadResource;
use App\Models\Blog;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\Lead;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class AdminOverview extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = '5s';

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
        $returnedToMeLeads = Lead::query()
            ->returnedToPrimaryUser($user)
            ->count();

        $leadStat = Stat::make($user->isAdmin() ? 'Нови заявки' : 'Моите заявки', $newLeads)
            ->description($user->isAdmin() ? "Общо {$totalLeads} заявки" : "Общо {$totalLeads} ваши заявки")
            ->icon(Heroicon::OutlinedClipboardDocumentList)
            ->color($newLeads > 0 ? 'warning' : 'gray')
            ->url(LeadResource::getUrl());

        $returnedToMeStat = Stat::make('Върнати към мен', $returnedToMeLeads)
            ->description('Върнати от допълнителен служител')
            ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
            ->color($returnedToMeLeads > 0 ? 'info' : 'gray')
            ->url(ReturnedToMeLeadResource::getUrl());

        if (! $user->isAdmin()) {
            return [$leadStat, $returnedToMeStat];
        }

        $todayLeads = Lead::query()
            ->whereBetween('created_at', $this->getTodayLeadRange())
            ->count();
        $messages = ContactMessage::count();
        $publishedBlogs = Blog::query()->where('is_published', true)->count();
        $publishedFaqs = Faq::query()->where('is_published', true)->count();

        return [
            Stat::make('Получени заявки днес', $todayLeads)
                ->description('Занулява се всеки ден в 00:00 ч.')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color($todayLeads > 0 ? 'success' : 'gray')
                ->url(LeadResource::getUrl()),
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

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function getTodayLeadRange(): array
    {
        $todayInSofia = CarbonImmutable::now('Europe/Sofia');

        return [
            $todayInSofia->startOfDay()->utc(),
            $todayInSofia->endOfDay()->utc(),
        ];
    }

    private function getVisibleLeadsQuery(User $user): Builder
    {
        $query = Lead::query();

        if ($user->isAdmin()) {
            return $query;
        }

        return $query->visibleToUser($user);
    }
}
