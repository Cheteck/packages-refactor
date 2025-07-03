{{-- resources/views/user-management/users/show.blade.php --}}
<x-app-layout>
    <div class="min-h-screen bg-gray-50">
        <!-- Cover Photo -->
        <div class="relative h-72 md:h-96">
            @if($user->cover_photo_path)
                <img src="{{ asset($user->cover_photo_path) }}" alt="Cover Photo" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
            @else
                <div class="w-full h-full bg-gradient-to-r from-indigo-500 via-purple-500 to-pink-500"></div>
            @endif
        </div>

        <!-- Profile Content -->
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20">
            <!-- Profile Card -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <!-- Profile Header -->
                <div class="relative pt-16 pb-8 px-6">
                    <!-- Profile Photo -->
                    <div class="absolute -top-16 left-6">
                        @if($user->profile_photo_path)
                            <img src="{{ asset($user->profile_photo_path) }}" alt="Profile Photo" class="w-32 h-32 rounded-full border-4 border-white shadow-md object-cover">
                        @else
                            <div class="w-32 h-32 rounded-full border-4 border-white bg-gray-200 flex items-center justify-center shadow-md">
                                <span class="text-3xl font-bold text-gray-600">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Name and Username -->
                    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">{{ $user->name }}</h1>
                            <p class="text-lg text-gray-500">@ {{ $user->username ?? 'N/A' }}</p>
                        </div>
                        <!-- Action Buttons -->
                        <div class="mt-4 sm:mt-0 flex space-x-3">
                            @auth
                                @if(auth()->id() === $user->id)
                                    <a href="{{ route('user-management.users.edit', $user->id) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-semibold rounded-lg shadow hover:bg-indigo-700 transition duration-200">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        Modifier le profil
                                    </a>
                                @else
                                    <button class="inline-flex items-center px-4 py-2 bg-blue-500 text-white font-semibold rounded-lg shadow hover:bg-blue-600 transition duration-200">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        Suivre
                                    </button>
                                    <button class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 font-semibold rounded-lg shadow hover:bg-gray-200 transition duration-200">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
                                        Message
                                    </button>
                                @endif
                            @endauth
                        </div>
                    </div>
                </div>

                <!-- Profile Details -->
                <div class="border-t border-gray-200 px-6 py-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Bio and Info -->
                        <div class="md:col-span-2">
                            <h2 class="text-xl font-semibold text-gray-900 mb-3">À propos</h2>
                            <p class="text-gray-700 mb-4">{{ $user->bio ?? 'Aucune bio fournie.' }}</p>
                            <div class="space-y-3 text-gray-600">
                                @if($user->birthdate)
                                    <p class="flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        <span><strong>Date de naissance :</strong> {{ $user->birthdate->format('d M Y') }}</span>
                                    </p>
                                @endif
                                @if($user->gender)
                                    <p class="flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path></svg>
                                        <span><strong>Genre :</strong> {{ $user->gender }}</span>
                                    </p>
                                @endif
                                @if($user->phone)
                                    <p class="flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                        <span><strong>Téléphone :</strong> {{ $user->phone }}</span>
                                    </p>
                                @endif
                                @if($user->preferred_language)
                                    <p class="flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m-3 2h12M9 7v2m-3 2h12M9 11v2m-3 2h12M9 15v2m-3 2h12M9 19v2"></path></svg>
                                        <span><strong>Langue préférée :</strong> {{ $user->preferred_language }}</span>
                                    </p>
                                @endif
                                @if($user->location)
                                    <p class="flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"></path></svg>
                                        <span><strong>Localisation :</strong> {{ $user->location }}</span>
                                    </p>
                                @endif
                                @if($user->website)
                                    <p class="flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                        <span><strong>Site web :</strong> <a href="{{ $user->website }}" target="_blank" class="text-indigo-500 hover:underline">{{ $user->website }}</a></span>
                                    </p>
                                @endif
                            </div>
                        </div>

                        <!-- Stats Section -->
                        <div class="text-center md:text-left">
                            <h2 class="text-xl font-semibold text-gray-900 mb-3">Statistiques</h2>
                            <div class="flex flex-col space-y-4">
                                <div class="flex items-center justify-center md:justify-start bg-indigo-50 rounded-lg p-3">
                                    <svg class="w-6 h-6 text-indigo-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                    <span><strong>Abonnés :</strong> {{ $user->followers_count ?? 0 }}</span>
                                </div>
                                <div class="flex items-center justify-center md:justify-start bg-indigo-50 rounded-lg p-3">
                                    <svg class="w-6 h-6 text-indigo-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                                    <span><strong>Abonnements :</strong> {{ $user->following_count ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
