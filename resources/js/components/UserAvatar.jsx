import React, { useState } from 'react';

export default function UserAvatar({ user, size = 'w-8 h-8', textSize = 'text-xs' }) {
  const [hasError, setHasError] = useState(false);

  const rawAvatar = user?.avatar_url || user?.avatar;

  if (rawAvatar && !hasError) {
    let avatarSrc = rawAvatar;
    if (!rawAvatar.startsWith('http')) {
      avatarSrc = rawAvatar.startsWith('/') ? rawAvatar : `/storage/${rawAvatar}`;
      const pathname = typeof window !== 'undefined' ? window.location.pathname : '';
      if (pathname.startsWith('/leaves-application') && !avatarSrc.startsWith('/leaves-application')) {
        avatarSrc = `/leaves-application${avatarSrc}`;
      }
    }

    return (
      <img
        src={avatarSrc}
        alt={user?.name || 'User Profile'}
        onError={() => setHasError(true)}
        className={`${size} rounded-full object-cover ring-2 ring-emerald-500/30 shrink-0 shadow-sm`}
      />
    );
  }

  return (
    <div className={`${size} rounded-full bg-emerald-600 text-white flex items-center justify-center font-extrabold ${textSize} shadow-sm ring-2 ring-emerald-500/30 shrink-0`}>
      {user?.name ? user.name.charAt(0).toUpperCase() : 'U'}
    </div>
  );
}

export { UserAvatar };
