@extends('vendor.nova.layout')

<div class="flex min-h-screen">
    <!--sidebar-->
    <div class="min-h-screen flex-none pt-header min-h-screen w-sidebar bg-grad-sidebar px-6"><a href="/">
            <div class="absolute pin-t pin-l pin-r bg-logo flex items-center w-sidebar h-header px-6 text-white">
                <svg width="126" height="24" viewBox="0 0 126 24" xmlns="http://www.w3.org/2000/svg"
                     class="fill-current">
                    <path d="M40.76 18h-6.8V7.328h2.288V16h4.512v2zm8.064 0h-2.048v-.816c-.528.64-1.44 1.008-2.448 1.008-1.232 0-2.672-.832-2.672-2.56 0-1.824 1.44-2.496 2.672-2.496 1.04 0 1.936.336 2.448.944v-.976c0-.784-.672-1.296-1.696-1.296-.816 0-1.584.32-2.224.912l-.8-1.424c.944-.848 2.16-1.216 3.376-1.216 1.776 0 3.392.704 3.392 2.928V18zm-3.68-1.184c.656 0 1.296-.224 1.632-.672v-.96c-.336-.448-.976-.688-1.632-.688-.8 0-1.456.432-1.456 1.168s.656 1.152 1.456 1.152zM52.856 18h-2.032v-7.728h2.032v1.04c.56-.672 1.504-1.232 2.464-1.232v1.984a2.595 2.595 0 0 0-.56-.048c-.672 0-1.568.384-1.904.88V18zm10.416 0h-2.048v-.816c-.528.64-1.44 1.008-2.448 1.008-1.232 0-2.672-.832-2.672-2.56 0-1.824 1.44-2.496 2.672-2.496 1.04 0 1.936.336 2.448.944v-.976c0-.784-.672-1.296-1.696-1.296-.816 0-1.584.32-2.224.912l-.8-1.424c.944-.848 2.16-1.216 3.376-1.216 1.776 0 3.392.704 3.392 2.928V18zm-3.68-1.184c.656 0 1.296-.224 1.632-.672v-.96c-.336-.448-.976-.688-1.632-.688-.8 0-1.456.432-1.456 1.168s.656 1.152 1.456 1.152zM69.464 18h-2.192l-3.104-7.728h2.176l2.016 5.376 2.032-5.376h2.176L69.464 18zm7.648.192c-2.352 0-4.128-1.584-4.128-4.064 0-2.24 1.664-4.048 4-4.048 2.32 0 3.872 1.728 3.872 4.24v.48h-5.744c.144.944.912 1.728 2.224 1.728.656 0 1.552-.272 2.048-.752l.912 1.344c-.768.704-1.984 1.072-3.184 1.072zm1.792-4.8c-.064-.736-.576-1.648-1.92-1.648-1.264 0-1.808.88-1.888 1.648h3.808zM84.36 18h-2.032V7.328h2.032V18zm15.232 0h-1.28l-6.224-8.512V18H90.76V7.328h1.36l6.144 8.336V7.328h1.328V18zm5.824.192c-2.352 0-3.824-1.824-3.824-4.064s1.472-4.048 3.824-4.048 3.824 1.808 3.824 4.048-1.472 4.064-3.824 4.064zm0-1.072c1.648 0 2.56-1.408 2.56-2.992 0-1.568-.912-2.976-2.56-2.976-1.648 0-2.56 1.408-2.56 2.976 0 1.584.912 2.992 2.56 2.992zm9.152.88h-1.312l-3.216-7.728h1.312l2.56 6.336 2.576-6.336h1.296L114.568 18zm10.496 0h-1.2v-.88c-.624.704-1.52 1.072-2.56 1.072-1.296 0-2.688-.88-2.688-2.56 0-1.744 1.376-2.544 2.688-2.544 1.056 0 1.936.336 2.56 1.04v-1.392c0-1.024-.832-1.616-1.952-1.616-.928 0-1.68.32-2.368 1.072l-.56-.832c.832-.864 1.824-1.28 3.088-1.28 1.648 0 2.992.736 2.992 2.608V18zm-3.312-.672c.832 0 1.648-.32 2.112-.96v-1.472c-.464-.624-1.28-.944-2.112-.944-1.136 0-1.92.704-1.92 1.68 0 .992.784 1.696 1.92 1.696zM20.119 20.455A12.184 12.184 0 0 1 11.5 24a12.18 12.18 0 0 1-9.333-4.319c4.772 3.933 11.88 3.687 16.36-.738a7.571 7.571 0 0 0 0-10.8c-3.018-2.982-7.912-2.982-10.931 0a3.245 3.245 0 0 0 0 4.628 3.342 3.342 0 0 0 4.685 0 1.114 1.114 0 0 1 1.561 0 1.082 1.082 0 0 1 0 1.543 5.57 5.57 0 0 1-7.808 0 5.408 5.408 0 0 1 0-7.714c3.881-3.834 10.174-3.834 14.055 0a9.734 9.734 0 0 1 .03 13.855zm.714-16.136C16.06.386 8.953.632 4.473 5.057a7.571 7.571 0 0 0 0 10.8c3.018 2.982 7.912 2.982 10.931 0a3.245 3.245 0 0 0 0-4.628 3.342 3.342 0 0 0-4.685 0 1.114 1.114 0 0 1-1.561 0 1.082 1.082 0 0 1 0-1.543 5.57 5.57 0 0 1 7.808 0 5.408 5.408 0 0 1 0 7.714c-3.881 3.834-10.174 3.834-14.055 0a9.734 9.734 0 0 1-.015-13.87C5.096 1.35 8.138 0 11.5 0c3.75 0 7.105 1.68 9.333 4.319z"
                          fill-rule="evenodd"></path>
                </svg>
            </div>
        </a>
        <h3 class="cursor-pointer flex items-center font-normal dim text-white mb-8 text-base no-underline router-link-exact-active router-link-active">
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 20 20"
                 class="sidebar-icon">
                <defs>
                    <path id="b"
                          d="M11 18v-5H9v5c0 1.1045695-.8954305 2-2 2H4c-1.1045695 0-2-.8954305-2-2v-7.5857864l-.29289322.2928932c-.39052429.3905243-1.02368927.3905243-1.41421356 0-.3905243-.3905243-.3905243-1.02368929 0-1.41421358l9-9C9.48815536.09763107 9.74407768 0 10 0c.2559223 0 .5118446.09763107.7071068.29289322l9 9c.3905243.39052429.3905243 1.02368928 0 1.41421358-.3905243.3905243-1.0236893.3905243-1.4142136 0L18 10.4142136V18c0 1.1045695-.8954305 2-2 2h-3c-1.1045695 0-2-.8954305-2-2zm5 0V8.41421356l-6-6-6 6V18h3v-5c0-1.1045695.8954305-2 2-2h2c1.1045695 0 2 .8954305 2 2v5h3z"></path>
                    <filter id="a" width="135%" height="135%" x="-17.5%" y="-12.5%" filterUnits="objectBoundingBox">
                        <feOffset dy="1" in="SourceAlpha" result="shadowOffsetOuter1"></feOffset>
                        <feGaussianBlur in="shadowOffsetOuter1" result="shadowBlurOuter1"
                                        stdDeviation="1"></feGaussianBlur>
                        <feColorMatrix in="shadowBlurOuter1"
                                       values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.166610054 0"></feColorMatrix>
                    </filter>
                </defs>
                <g fill="none" fill-rule="evenodd">
                    <use fill="#000" filter="url(#a)" xlink:href="#b"></use>
                    <use fill="var(--sidebar-icon)" xlink:href="#b"></use>
                </g>
            </svg>
            <span class="text-white sidebar-label">Dashboard</span></h3>
        <h3 class="flex items-center font-normal text-white mb-6 text-base no-underline">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="sidebar-icon">
                <path fill="var(--sidebar-icon)"
                      d="M3 1h4c1.1045695 0 2 .8954305 2 2v4c0 1.1045695-.8954305 2-2 2H3c-1.1045695 0-2-.8954305-2-2V3c0-1.1045695.8954305-2 2-2zm0 2v4h4V3H3zm10-2h4c1.1045695 0 2 .8954305 2 2v4c0 1.1045695-.8954305 2-2 2h-4c-1.1045695 0-2-.8954305-2-2V3c0-1.1045695.8954305-2 2-2zm0 2v4h4V3h-4zM3 11h4c1.1045695 0 2 .8954305 2 2v4c0 1.1045695-.8954305 2-2 2H3c-1.1045695 0-2-.8954305-2-2v-4c0-1.1045695.8954305-2 2-2zm0 2v4h4v-4H3zm10-2h4c1.1045695 0 2 .8954305 2 2v4c0 1.1045695-.8954305 2-2 2h-4c-1.1045695 0-2-.8954305-2-2v-4c0-1.1045695.8954305-2 2-2zm0 2v4h4v-4h-4z"></path>
            </svg>
            <span class="sidebar-label">Resources</span></h3>
        <h4 class="ml-8 mb-4 text-xs text-white-50% uppercase tracking-wide">Editorial Content</h4>
        <ul class="list-reset mb-8">
            <li class="leading-tight mb-4 ml-8 text-sm"><a href="/resources/apps"
                                                           class="text-white text-justify no-underline dim"
                                                           dusk="apps-resource-link">
                    Apps
                </a></li>
            <li class="leading-tight mb-4 ml-8 text-sm"><a href="/resources/ec-medias"
                                                           class="text-white text-justify no-underline dim"
                                                           dusk="ec-medias-resource-link">
                    Ec Medias
                </a></li>
            <li class="leading-tight mb-4 ml-8 text-sm"><a href="/resources/ec-tracks"
                                                           class="text-white text-justify no-underline dim"
                                                           dusk="ec-tracks-resource-link">
                    Ec Tracks
                </a></li>
            <li class="leading-tight mb-4 ml-8 text-sm"><a href="/resources/ec-pois"
                                                           class="text-white text-justify no-underline dim"
                                                           dusk="ec-pois-resource-link">
                    Ec Pois
                </a></li>
        </ul>
        <h4 class="ml-8 mb-4 text-xs text-white-50% uppercase tracking-wide">Taxonomies</h4>
        <ul class="list-reset mb-8">
            <li class="leading-tight mb-4 ml-8 text-sm"><a href="/resources/taxonomy-wheres"
                                                           class="text-white text-justify no-underline dim"
                                                           dusk="taxonomy-wheres-resource-link">
                    Taxonomy Wheres
                </a></li>
            <li class="leading-tight mb-4 ml-8 text-sm"><a href="/resources/taxonomy-activities"
                                                           class="text-white text-justify no-underline dim"
                                                           dusk="taxonomy-activities-resource-link">
                    Taxonomy Activities
                </a></li>
            <li class="leading-tight mb-4 ml-8 text-sm"><a href="/resources/taxonomy-poi-types"
                                                           class="text-white text-justify no-underline dim"
                                                           dusk="taxonomy-poi-types-resource-link">
                    Taxonomy Poi Types
                </a></li>
            <li class="leading-tight mb-4 ml-8 text-sm"><a href="/resources/taxonomy-whens"
                                                           class="text-white text-justify no-underline dim"
                                                           dusk="taxonomy-whens-resource-link">
                    Taxonomy Whens
                </a></li>
            <li class="leading-tight mb-4 ml-8 text-sm"><a href="/resources/taxonomy-targets"
                                                           class="text-white text-justify no-underline dim"
                                                           dusk="taxonomy-targets-resource-link">
                    Taxonomy Targets
                </a></li>
            <li class="leading-tight mb-4 ml-8 text-sm"><a href="/resources/taxonomy-themes"
                                                           class="text-white text-justify no-underline dim"
                                                           dusk="taxonomy-themes-resource-link">
                    Taxonomy Themes
                </a></li>
        </ul>
        <h4 class="ml-8 mb-4 text-xs text-white-50% uppercase tracking-wide">User Generated Content</h4>
        <ul class="list-reset mb-8">
            <li class="leading-tight mb-4 ml-8 text-sm"><a href="/resources/ugc-pois"
                                                           class="text-white text-justify no-underline dim"
                                                           dusk="ugc-pois-resource-link">
                    Ugc Pois
                </a></li>
            <li class="leading-tight mb-4 ml-8 text-sm"><a href="/resources/ugc-tracks"
                                                           class="text-white text-justify no-underline dim"
                                                           dusk="ugc-tracks-resource-link">
                    Ugc Tracks
                </a></li>
            <li class="leading-tight mb-4 ml-8 text-sm"><a href="/resources/ugc-medias"
                                                           class="text-white text-justify no-underline dim"
                                                           dusk="ugc-medias-resource-link">
                    Ugc Medias
                </a></li>
        </ul>
        <h4 class="ml-8 mb-4 text-xs text-white-50% uppercase tracking-wide">Admin</h4>
        <ul class="list-reset mb-8">
            <li class="leading-tight mb-4 ml-8 text-sm"><a href="/resources/users"
                                                           class="text-white text-justify no-underline dim"
                                                           dusk="users-resource-link">
                    Users
                </a></li>
            <li class="leading-tight mb-4 ml-8 text-sm"><a href="/resources/roles"
                                                           class="text-white text-justify no-underline dim"
                                                           dusk="roles-resource-link">
                    Roles
                </a></li>
            <li class="leading-tight mb-4 ml-8 text-sm"><a href="/resources/permissions"
                                                           class="text-white text-justify no-underline dim"
                                                           dusk="permissions-resource-link">
                    Permissions
                </a></li>
        </ul>
        <h3 class="cursor-pointer flex items-center font-normal dim text-white mb-6 text-base no-underline">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" class="sidebar-icon">
                <path fill="var(--sidebar-icon)"
                      d="M3 1h4c1.1045695 0 2 .8954305 2 2v4c0 1.1045695-.8954305 2-2 2H3c-1.1045695 0-2-.8954305-2-2V3c0-1.1045695.8954305-2 2-2zm0 2v4h4V3H3zm10-2h4c1.1045695 0 2 .8954305 2 2v4c0 1.1045695-.8954305 2-2 2h-4c-1.1045695 0-2-.8954305-2-2V3c0-1.1045695.8954305-2 2-2zm0 2v4h4V3h-4zM3 11h4c1.1045695 0 2 .8954305 2 2v4c0 1.1045695-.8954305 2-2 2H3c-1.1045695 0-2-.8954305-2-2v-4c0-1.1045695.8954305-2 2-2zm0 2v4h4v-4H3zm10-2h4c1.1045695 0 2 .8954305 2 2v4c0 1.1045695-.8954305 2-2 2h-4c-1.1045695 0-2-.8954305-2-2v-4c0-1.1045695.8954305-2 2-2zm0 2v4h4v-4h-4z"></path>
            </svg>
            <span class="sidebar-label">
        Import
    </span></h3>
    </div>
    <!--end sidebar-->

    <!-- Content -->
    <div class="content">
        <div class="flex items-center relative shadow h-header bg-white z-20 px-view">
            <a href="{{ \Illuminate\Support\Facades\Config::get('nova.url') }}"
               class="no-underline dim font-bold text-90 mr-6">
                {{ \Laravel\Nova\Nova::name() }}
            </a>

            @if (count(\Laravel\Nova\Nova::globallySearchableResources(request())) > 0)
                <global-search dusk="global-search-component"></global-search>
            @endif

            <dropdown class="ml-auto h-9 flex items-center dropdown-right">
                @include('nova::partials.user')
            </dropdown>
        </div>
        <h1 class="px-view py-2 mx-auto">Import Preview</h1>
        <div data-testid="content" class="px-view py-view mx-auto">
            @php $jsonFeature = json_encode($features) @endphp

            @foreach($features->features as $feature)
                @if(isset($feature->properties->name))
                    <h2>Feature Name: {{$feature->properties->name}}</h2>
                    <br>
                @else
                    @php $feature->properties->name = 'ecTrack_'.date('Y-m-d') ; @endphp
                    <h2>Feature Name: {{$feature->properties->name}}</h2>
                    <br>
                @endif
                <p>Feature Type: {{$feature->geometry->type}}</p>
                <br>
            @endforeach
            <div class="container">

                <form action="/import/confirm" method="POST">
                    @csrf
                    <div style="display:flex">
                        <a class="btn btn-default btn-danger mx-2" href="/import">Annulla</a>
                        <input type="hidden" name="features" value="{{$jsonFeature}}"/>
                        <button type="submit" class="btn btn-default btn-primary mx-2">Conferma</button>
                    </div>
                </form>
            </div>

            @include('nova::partials.footer')
        </div>
    </div>


</div>