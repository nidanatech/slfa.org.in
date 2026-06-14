# Project Sidebar Design Protocol (PMBOK Control Room)

To maintain UI/UX consistency across all project management sidebars and forms, follow these styling rules derived from the Core CRM design system:

## 1. Sidebar Architecture (Minimalist White)
- **Header**: Use a clean white header (`bg-white`) with a bottom border (`border-b border-slate-100`).
- **Title**: Large, bold uppercase tracking. Class: `text-xl font-black uppercase tracking-tight text-slate-900`.
- **Subtitle/Instruction**: Small, legible text explaining the purpose. Class: `text-[10px] font-medium text-slate-500 leading-relaxed max-w-[80%]`.
- **Navigation**: Use a floating close button in the top-right of the header. Class: `absolute top-8 right-8 z-50 p-2 rounded-full bg-slate-50 hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-all`.
- **Content Area**: Use a subtle off-white background (`bg-slate-50/30`) for the form body to distinguish it from the header.

## 2. Field Layout & Typography
- **Labels**: Standard semibold weight. Class: `text-sm font-semibold text-slate-900 mb-2 block`.
- **Inputs**: Standardized rounded-lg height. Class: `h-11 border-slate-200 rounded-lg bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-all`.
- **Spacing**: Form groups should be separated by `space-y-8`.

## 3. Action Footers & Buttons
- **Footer**: `p-8 border-t border-slate-100 bg-white flex justify-end gap-3`.
- **Secondary Button (Cancel/Dismiss)**: White background, gray border, bold black text. Class: `h-12 rounded-lg border-slate-200 font-semibold text-slate-900 px-8 hover:bg-slate-50 transition-all`.
- **Primary Button (Save/Create/Update)**: Dark background, bold white text. Class: `h-12 rounded-lg bg-slate-900 border-slate-900 font-semibold text-white px-8 hover:bg-slate-800 transition-all shadow-sm`.

## 4. Interactive Components
- **Data Selection**: Use `SearchableSelect` for all entity pickups (contacts, projects, etc.).
- **AI Primary Action**: Prominent full-width trigger button below metadata fields. Class: `w-full h-12 rounded-xl bg-[#0B1F33] hover:bg-[#0B1F33]/90 text-white font-black text-[10px] uppercase tracking-widest shadow-lg shadow-indigo-100 transition-all flex items-center justify-center gap-3`.

These rules ensure that advanced governance tools feel like a native, premium extension of the Sydkic platform.
