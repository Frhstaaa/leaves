import React from 'react';
import { Skeleton } from '@/components/ui/skeleton';

export function DashboardSkeleton() {
  return (
    <div className="space-y-6 animate-fadeIn">
      {/* Top Greeting Skeleton */}
      <div className="flex items-center justify-between gap-3">
        <div className="flex items-center space-x-3 sm:space-x-4">
          <Skeleton className="w-12 h-12 sm:w-14 sm:h-14 rounded-full" />
          <div className="space-y-2">
            <Skeleton className="h-6 w-40 sm:w-56 rounded-lg" />
            <Skeleton className="h-3.5 w-32 sm:w-48 rounded-md" />
          </div>
        </div>
        <div className="flex items-center space-x-2">
          <Skeleton className="h-10 w-10 rounded-2xl md:hidden" />
          <Skeleton className="h-10 w-36 rounded-2xl hidden sm:block" />
        </div>
      </div>

      {/* 3 Metric Cards Skeleton */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
        {[1, 2, 3].map((i) => (
          <div key={i} className="p-5 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-3">
            <div className="flex items-center justify-between">
              <Skeleton className="h-4 w-24 rounded-md" />
              <Skeleton className="w-8 h-8 rounded-xl" />
            </div>
            <Skeleton className="h-8 w-20 rounded-lg" />
            <Skeleton className="h-3 w-32 rounded-md" />
          </div>
        ))}
      </div>

      {/* Quota & Recent Requests Skeleton Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-5">
        {/* Quota Card Skeleton */}
        <div className="p-6 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-4">
          <Skeleton className="h-5 w-32 rounded-lg" />
          <div className="flex justify-center py-4">
            <Skeleton className="w-32 h-32 rounded-full" />
          </div>
          <div className="space-y-2">
            <Skeleton className="h-4 w-full rounded-md" />
            <Skeleton className="h-4 w-4/5 rounded-md" />
          </div>
        </div>

        {/* Recent Requests Table Skeleton */}
        <div className="lg:col-span-2 p-6 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-4">
          <div className="flex items-center justify-between">
            <Skeleton className="h-5 w-40 rounded-lg" />
            <Skeleton className="h-4 w-20 rounded-md" />
          </div>
          <div className="space-y-3 pt-2">
            {[1, 2, 3, 4].map((i) => (
              <div key={i} className="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between gap-3">
                <div className="flex items-center space-x-3 min-w-0">
                  <Skeleton className="w-9 h-9 rounded-xl shrink-0" />
                  <div className="space-y-1.5 min-w-0">
                    <Skeleton className="h-4 w-32 sm:w-48 rounded-md" />
                    <Skeleton className="h-3 w-24 sm:w-36 rounded-md" />
                  </div>
                </div>
                <Skeleton className="h-6 w-20 rounded-full shrink-0" />
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}

export function TableSkeleton({ rows = 5 }) {
  return (
    <div className="space-y-5 animate-fadeIn">
      {/* Search & Filter Bar Skeleton */}
      <div className="p-4 rounded-3xl bg-white border border-slate-200 shadow-sm flex flex-col sm:flex-row gap-3 items-center justify-between">
        <Skeleton className="h-10 w-full sm:w-72 rounded-2xl" />
        <div className="flex items-center gap-2 w-full sm:w-auto">
          <Skeleton className="h-10 w-full sm:w-36 rounded-2xl" />
          <Skeleton className="h-10 w-full sm:w-36 rounded-2xl" />
        </div>
      </div>

      {/* Table Box Skeleton */}
      <div className="rounded-3xl bg-white border border-slate-200 shadow-sm overflow-hidden p-4 sm:p-6 space-y-4">
        <div className="flex items-center justify-between pb-3 border-b border-slate-100">
          <Skeleton className="h-5 w-36 rounded-lg" />
          <Skeleton className="h-8 w-28 rounded-xl" />
        </div>

        <div className="space-y-3">
          {Array.from({ length: rows }).map((_, i) => (
            <div key={i} className="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between gap-3">
              <div className="flex items-center space-x-3 min-w-0 flex-1">
                <Skeleton className="w-10 h-10 rounded-2xl shrink-0" />
                <div className="space-y-2 flex-1 min-w-0">
                  <Skeleton className="h-4 w-40 sm:w-64 rounded-md" />
                  <Skeleton className="h-3 w-28 sm:w-48 rounded-md" />
                </div>
              </div>
              <div className="flex items-center space-x-2 shrink-0">
                <Skeleton className="h-7 w-20 rounded-full hidden sm:block" />
                <Skeleton className="h-8 w-8 rounded-xl" />
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

export function CardListSkeleton({ count = 4 }) {
  return (
    <div className="space-y-5 animate-fadeIn">
      {/* Search Bar Skeleton */}
      <div className="p-4 rounded-3xl bg-white border border-slate-200 shadow-sm flex flex-col sm:flex-row gap-3 items-center justify-between">
        <Skeleton className="h-10 w-full sm:w-80 rounded-2xl" />
        <Skeleton className="h-10 w-full sm:w-32 rounded-2xl" />
      </div>

      {/* Grid of Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {Array.from({ length: count }).map((_, i) => (
          <div key={i} className="p-5 rounded-3xl bg-white border border-slate-200 shadow-sm space-y-4">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-3">
                <Skeleton className="w-10 h-10 rounded-2xl" />
                <div className="space-y-1.5">
                  <Skeleton className="h-4 w-32 rounded-md" />
                  <Skeleton className="h-3 w-24 rounded-md" />
                </div>
              </div>
              <Skeleton className="h-6 w-20 rounded-full" />
            </div>
            <Skeleton className="h-16 w-full rounded-2xl" />
            <div className="flex items-center justify-between pt-2 border-t border-slate-100">
              <Skeleton className="h-8 w-24 rounded-xl" />
              <div className="flex space-x-2">
                <Skeleton className="h-8 w-20 rounded-xl" />
                <Skeleton className="h-8 w-20 rounded-xl" />
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
