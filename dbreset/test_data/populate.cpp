#include <iostream>
#include <fstream>
#include <string>
#include <stdlib.h>
using namespace std;

int main() {
	string instName [] = {"Trumpet", "Trombone", "Tenor sax", "Alto sax", "Piccolo", "Clarinet", "Sousaphone", "Baritone", "Mellophone"};
	string condition [] = {"needs repair", "Good", "Fair", "Poor", "new"};
	string firstName [] = {"Bob", "Bill", "Gina", "Jennifer", "Tom", "George", "Chris", "Caroline", "Rachel", "Jill"};
	string lastName [] = {"Smith", "Vasquez", "Todd", "Adams", "Willis", "Green", "Doofenshmirtz", "Hill"};
	string role [] = {"user", "user", "user", "user", "manager", "manager", "admin"};
	string address [] = {"Mulberry st", "Lois ln", "Linco ln", "Be st", "Round cir"};
	string city [] = {"Chernobyl", "Minas Tirith", "Clemson", "Columbia", "Gungan", "City city"};
	string state [] = {"Ofmind", "Gondor", "Narnia", "South Carolina", "Solid", "Plasma"};
	string boolean [] = {"true", "false"};
	ofstream students;
	int cuid = 1000000;
	int serialNo = 1000;
	ofstream instruments;
	students.open("student_big_data.csv");
	instruments.open("instruments_big_data.csv");
	for(int i = 0; i < 150; i++)
	{
		string fName = firstName[rand()%10];
		string lName = lastName[rand()%8];
		string inst = instName[rand()%9];
		string cond = condition[rand()%5];
		students << cuid++ <<   "|" << fName + lName << "|" << 
			lName + fName << "|" << role[rand()%7] << "|" << 
			fName << "|" << lName << "|" << 
			fName + lName + "@g.clemson.edu" << "|" << 
			rand()%9000 + 1000 << " " << address[rand()%5] << 
			", " << city[rand()%6] << ", "<< state[rand()%6] << 
			"|" << (rand()%32) + 18 << "|" << 
			(rand()%899) + 100 << "-" << (rand()%899) + 100 << 
			"-" << (rand()%8999) + 1000 << endl;
		instruments << serialNo++ <<  "," << inst << "," << cond << 
				endl; 
	}
	ofstream rentalCont;
	ofstream activeCont;
	rentalCont.open("rental_contracts_big_data.csv");
	for(int i = 0; i < 150; i++)
	{
		int startYear = (rand()%100) + 1985;
		int endYear = (rand()%100) + 1985;
		int startMonth = (rand()%12) + 1;
		int endMonth = (rand()%12) + 1;
		int startDay = (rand()%28) + 1;
		int endDay = (rand()%28) + 1;
		while(startYear > endYear)
		{
			endYear++;
		}
		if(startYear == endYear)
		{
			while(startMonth > endMonth)
			{
				endMonth++;
				if(endMonth > 12)
				{
					endYear++;
					endMonth -= 12;
				}
			}
			if(startMonth == endMonth)
			{
				while(startDay > endDay)
				{
					if(endDay < 28)
					{
						endDay++;
					}
					else
					{
						startDay--;
					}
				}
			}
		}
		rentalCont << startYear << "-" << startMonth << "-" << startDay
			<< "," << endYear << "-" << endMonth << "-" << endDay
			<< "," << (rand()%150) + 1000000 << "," 
			<< (rand()%150) + 1000 << "," << boolean[rand()%2] << endl; 
	} 
	activeCont.open("active_contract_big_data.csv");
	int instrument = 1000;
	while(instrument <= 1150)
	{
		int startYear = 2017 - rand()%50;
		int endYear = rand()%50 + 2018;
		int startMonth = rand()%12 + 1;
		int endMonth = rand()%12 + 1;
		int startDay = rand()%28 + 1;
		int endDay = rand()%28 + 1;
		if(startYear == 2017)
		{
			if(startMonth > 11)
			{
				startMonth--;
			}
		}
		activeCont << startYear << "-" << startMonth << "-" << startDay
			<< "," << endYear << "-" << endMonth << "-" << endDay
			<< "," << (rand()%150) + 1000000 << "," << instrument
			<< endl;
		instrument += (rand()%2) + 1;
		
	}
	students.close();
	instruments.close();
	rentalCont.close();
	activeCont.close();
}
