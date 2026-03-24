@php
    use App\Filament\Resources\AttachedLeads\AttachedLeadResource;
    use App\Filament\Resources\ReturnedToMeLeads\ReturnedToMeLeadResource;
@endphp

<div>
    @livewire(\App\Livewire\AdminLeadAlerts::class, [], key('admin-lead-alerts'))

    @once
        <script>
            (() => {
                if (window.__creditZonaAdminLeadAlertsInitialized) {
                    return
                }

                window.__creditZonaAdminLeadAlertsInitialized = true

                const badgeTargets = [
                    {
                        path: @js(AttachedLeadResource::getUrl('index')),
                        key: 'attachedCount',
                    },
                    {
                        path: @js(ReturnedToMeLeadResource::getUrl('index')),
                        key: 'returnedToMeCount',
                    },
                ]

                const normalizeDetail = (event) => {
                    if (Array.isArray(event.detail)) {
                        return event.detail[0] ?? {}
                    }

                    return event.detail ?? {}
                }

                const findSidebarButton = (targetPath) => {
                    const normalizedPath = new URL(
                        targetPath,
                        window.location.origin,
                    ).pathname

                    return Array.from(
                        document.querySelectorAll('.fi-sidebar-item-btn[href]'),
                    ).find((button) => {
                        try {
                            return (
                                new URL(
                                    button.getAttribute('href'),
                                    window.location.origin,
                                ).pathname === normalizedPath
                            )
                        } catch (error) {
                            return false
                        }
                    })
                }

                const createBadgeElements = () => {
                    const container = document.createElement('span')
                    container.className = 'fi-sidebar-item-badge-ctn'

                    const badge = document.createElement('span')
                    badge.className =
                        'fi-badge fi-size-xs fi-color fi-color-primary'

                    const labelContainer = document.createElement('span')
                    labelContainer.className = 'fi-badge-label-ctn'

                    const label = document.createElement('span')
                    label.className = 'fi-badge-label'

                    labelContainer.appendChild(label)
                    badge.appendChild(labelContainer)
                    container.appendChild(badge)

                    return { container, label }
                }

                const updateSidebarBadge = (targetPath, count) => {
                    const button = findSidebarButton(targetPath)

                    if (!button) {
                        return
                    }

                    let container = button.querySelector(
                        '.fi-sidebar-item-badge-ctn',
                    )

                    if (!count) {
                        container?.remove()

                        return
                    }

                    let label = container?.querySelector('.fi-badge-label')

                    if (!label) {
                        const created = createBadgeElements()
                        container = created.container
                        label = created.label
                        button.appendChild(container)
                    }

                    label.textContent = String(count)
                }

                const sendToast = (payload) => {
                    if (
                        typeof window.FilamentNotification !== 'function' ||
                        !payload.title
                    ) {
                        return
                    }

                    const notification = new window.FilamentNotification()
                        .title(payload.title)
                        .persistent()

                    if (payload.body) {
                        notification.body(payload.body)
                    }

                    switch (payload.status) {
                        case 'warning':
                            notification.warning()
                            break
                        case 'danger':
                            notification.danger()
                            break
                        case 'success':
                            notification.success()
                            break
                        default:
                            notification.info()
                            break
                    }

                    notification.send()
                }

                window.addEventListener(
                    'lead-navigation-counts-updated',
                    (event) => {
                        const detail = normalizeDetail(event)

                        badgeTargets.forEach(({ path, key }) => {
                            updateSidebarBadge(path, Number(detail[key] ?? 0))
                        })
                    },
                )

                window.addEventListener('admin-lead-toast', (event) => {
                    sendToast(normalizeDetail(event))
                })
            })()
        </script>
    @endonce
</div>
