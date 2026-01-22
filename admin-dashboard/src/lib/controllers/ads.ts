import { NextResponse } from "next/server";

// Pattern: controller handles business logic, calls queries, returns response
// TODO: Implement after schema is finalized

export async function listAds() {
  return NextResponse.json({ message: "Not implemented" }, { status: 501 });
}

export async function getAd() {
  return NextResponse.json({ message: "Not implemented" }, { status: 501 });
}

export async function approveAd() {
  return NextResponse.json({ message: "Not implemented" }, { status: 501 });
}

export async function denyAd() {
  return NextResponse.json({ message: "Not implemented" }, { status: 501 });
}
