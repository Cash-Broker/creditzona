import { Calendar } from '@fullcalendar/core'
import bgLocale from '@fullcalendar/core/locales/bg'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import timeGridPlugin from '@fullcalendar/timegrid'

window.creditzonaCalendarPage = function creditzonaCalendarPage(config) {
    return {
        config,
        calendar: null,
        isDrawerOpen: false,
        selectedEvent: null,
        selectedEventDateLabel: '',
        filters: {
            userId: config.defaultUserFilter ?? '',
            eventType: '',
            status: '',
        },

        init() {
            this.initializeCalendar()

            window.addEventListener('admin-calendar-refresh', () => {
                this.closeDrawer()
                this.refetchEvents()
            })
        },

        initializeCalendar() {
            if (!this.$refs.calendar) {
                return
            }

            this.calendar = new Calendar(this.$refs.calendar, {
                plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
                locale: bgLocale,
                timeZone: 'Europe/Sofia',
                initialView: 'dayGridMonth',
                height: 'auto',
                firstDay: 1,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay',
                },
                buttonText: {
                    today: 'Днес',
                    month: 'Месец',
                    week: 'Седмица',
                    day: 'Ден',
                },
                dayMaxEvents: true,
                nowIndicator: true,
                editable: true,
                eventResizableFromStart: true,
                selectable: Boolean(config.canCreate),
                selectMirror: true,
                slotMinTime: '07:00:00',
                slotMaxTime: '21:00:00',
                allDaySlot: true,
                eventSources: [
                    (fetchInfo, successCallback, failureCallback) => {
                        this.fetchEvents(fetchInfo)
                            .then(successCallback)
                            .catch((error) => {
                                this.sendToast(
                                    this.resolveErrorMessage(error, 'Неуспешно зареждане на събитията.'),
                                    'danger',
                                )
                                failureCallback(error)
                            })
                    },
                ],
                select: (selectionInfo) => this.handleSelection(selectionInfo),
                eventClick: (eventInfo) => this.openDrawer(eventInfo.event),
                eventDrop: (eventInfo) => this.handleTimingChange(eventInfo),
                eventResize: (eventInfo) => this.handleTimingChange(eventInfo),
            })

            this.calendar.render()
        },

        async fetchEvents(fetchInfo) {
            const url = new URL(this.config.feedUrl, window.location.origin)

            url.searchParams.set('start', fetchInfo.startStr)
            url.searchParams.set('end', fetchInfo.endStr)

            if (this.filters.userId) {
                url.searchParams.set('user_id', this.filters.userId)
            }

            if (this.filters.eventType) {
                url.searchParams.set('event_type', this.filters.eventType)
            }

            if (this.filters.status) {
                url.searchParams.set('status', this.filters.status)
            }

            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            })

            if (!response.ok) {
                throw new Error('Неуспешно зареждане на събитията.')
            }

            return response.json()
        },

        handleSelection(selectionInfo) {
            if (!this.config.canCreate) {
                return
            }

            this.$wire.mountAction('createEvent', {
                starts_at: selectionInfo.startStr,
                ends_at: selectionInfo.endStr,
                all_day: selectionInfo.allDay,
                user_id: this.filters.userId || this.config.currentUserId,
            })

            this.calendar?.unselect()
        },

        async handleTimingChange(eventInfo) {
            try {
                await this.updateEventTiming(eventInfo.event)
                this.syncSelectedEventAfterMove(eventInfo.event)
                this.sendToast('Събитието е преместено.', 'success')
            } catch (error) {
                eventInfo.revert()

                this.sendToast(
                    this.resolveErrorMessage(error, 'Неуспешно преместване на събитието.'),
                    'danger',
                )
            }
        },

        async updateEventTiming(event) {
            const response = await fetch(
                this.config.timingUrlTemplate.replace('__CALENDAR_EVENT__', event.id),
                {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        starts_at: event.start?.toISOString(),
                        ends_at: event.end?.toISOString() ?? null,
                        all_day: event.allDay,
                    }),
                },
            )

            if (!response.ok) {
                const payload = await response.json().catch(() => ({}))
                const firstError = Object.values(payload.errors ?? {})[0]

                throw new Error(
                    Array.isArray(firstError) ? firstError[0] : payload.message,
                )
            }
        },

        openDrawer(event) {
            this.selectedEvent = {
                id: event.id,
                title: event.title,
                description: event.extendedProps.description ?? '',
                location: event.extendedProps.location ?? '',
                eventType: event.extendedProps.eventType ?? '',
                eventTypeLabel: event.extendedProps.eventTypeLabel ?? '',
                status: event.extendedProps.status ?? '',
                statusLabel: event.extendedProps.statusLabel ?? '',
                userName: event.extendedProps.userName ?? '',
                createdBy: event.extendedProps.createdBy ?? '',
                updatedBy: event.extendedProps.updatedBy ?? '',
                color: event.backgroundColor,
                allDay: event.allDay,
                canManage: Boolean(event.extendedProps.canManage),
                start: event.start,
                end: event.end,
            }

            this.selectedEventDateLabel = this.formatEventDateLabel(this.selectedEvent)
            this.isDrawerOpen = true
        },

        closeDrawer() {
            this.isDrawerOpen = false
            this.selectedEvent = null
            this.selectedEventDateLabel = ''
        },

        refetchEvents() {
            this.calendar?.refetchEvents()
        },

        syncSelectedEventAfterMove(event) {
            if (!this.selectedEvent || this.selectedEvent.id !== event.id) {
                return
            }

            this.selectedEvent.start = event.start
            this.selectedEvent.end = event.end
            this.selectedEvent.allDay = event.allDay
            this.selectedEventDateLabel = this.formatEventDateLabel(this.selectedEvent)
        },

        formatEventDateLabel(event) {
            if (!event?.start) {
                return ''
            }

            const dateFormatter = new Intl.DateTimeFormat('bg-BG', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            })

            const dateTimeFormatter = new Intl.DateTimeFormat('bg-BG', {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
            })

            if (event.allDay) {
                const endDate =
                    event.end && event.end > event.start
                        ? new Date(event.end.getTime() - 1000)
                        : event.start

                if (event.start.toDateString() === endDate.toDateString()) {
                    return `${dateFormatter.format(event.start)} • Целодневно`
                }

                return `${dateFormatter.format(event.start)} - ${dateFormatter.format(endDate)} • Целодневно`
            }

            if (!event.end) {
                return dateTimeFormatter.format(event.start)
            }

            return `${dateTimeFormatter.format(event.start)} - ${dateTimeFormatter.format(event.end)}`
        },

        sendToast(title, status = 'info') {
            if (!title || typeof window.FilamentNotification !== 'function') {
                return
            }

            const notification = new window.FilamentNotification().title(title)

            switch (status) {
                case 'success':
                    notification.success()
                    break
                case 'danger':
                    notification.danger()
                    break
                case 'warning':
                    notification.warning()
                    break
                default:
                    notification.info()
                    break
            }

            notification.send()
        },

        resolveErrorMessage(error, fallbackMessage) {
            if (error instanceof Error && error.message) {
                return error.message
            }

            return fallbackMessage
        },
    }
}
