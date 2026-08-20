import React from 'react';

function Skeleton({ className = '', ...props }) {
  return (
    <div
      className={`animate-pulse rounded-2xl bg-slate-200/90 relative overflow-hidden skeleton-shimmer ${className}`}
      {...props}
    />
  );
}

export { Skeleton };
