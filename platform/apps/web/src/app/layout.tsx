export const metadata = {
  title: 'Event Hosting Platform',
  description: 'Developer community events — Next.js scaffold',
};

export default function RootLayout({children}: {children: React.ReactNode}) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
