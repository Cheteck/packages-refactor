{{-- Example basic view for editing user details --}}
{{-- You would typically extend a layout file --}}
{{-- <!DOCTYPE html>
<html>
<head>
    <title>Edit Profile - {{ $user->name }}</title>
</head>
<body> --}}

    <h1>Edit Profile: {{ $user->name }}</h1>

    <form action="{{ route('user-management.users.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div>
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required>
            @error('name')
                <div style="color: red;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required>
            @error('email')
                <div style="color: red;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" value="{{ old('username', $user->username) }}">
            @error('username')
                <div style="color: red;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="profile_photo_path">Profile Photo URL:</label>
            <input type="text" id="profile_photo_path" name="profile_photo_path" value="{{ old('profile_photo_path', $user->profile_photo_path) }}">
            @error('profile_photo_path')
                <div style="color: red;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="cover_photo_path">Cover Photo URL:</label>
            <input type="text" id="cover_photo_path" name="cover_photo_path" value="{{ old('cover_photo_path', $user->cover_photo_path) }}">
            @error('cover_photo_path')
                <div style="color: red;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="bio">Bio:</label>
            <textarea id="bio" name="bio">{{ old('bio', $user->bio) }}</textarea>
            @error('bio')
                <div style="color: red;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="birthdate">Birthdate:</label>
            <input type="date" id="birthdate" name="birthdate" value="{{ old('birthdate', $user->birthdate ? $user->birthdate->format('Y-m-d') : '') }}">
            @error('birthdate')
                <div style="color: red;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="gender">Gender:</label>
            <input type="text" id="gender" name="gender" value="{{ old('gender', $user->gender) }}">
            @error('gender')
                <div style="color: red;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="phone">Phone:</label>
            <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
            @error('phone')
                <div style="color: red;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="preferred_language">Preferred Language:</label>
            <input type="text" id="preferred_language" name="preferred_language" value="{{ old('preferred_language', $user->preferred_language) }}">
            @error('preferred_language')
                <div style="color: red;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="location">Location:</label>
            <input type="text" id="location" name="location" value="{{ old('location', $user->location) }}">
            @error('location')
                <div style="color: red;">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label for="website">Website URL:</label>
            <input type="url" id="website" name="website" value="{{ old('website', $user->website) }}">
            @error('website')
                <div style="color: red;">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit">Update Profile</button>
    </form>

    <p><a href="{{ route('user-management.users.show', $user->id) }}">View Profile</a></p>

{{-- </body>
</html> --}}
