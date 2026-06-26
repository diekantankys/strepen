enum Gender { male, female, other }

Gender? genderFromString(String gender) {
  if (gender == 'male') return Gender.male;
  if (gender == 'female') return Gender.female;
  if (gender == 'other') return Gender.other;
  return null;
}

String genderToString(Gender? gender) {
  if (gender == Gender.male) return 'male';
  if (gender == Gender.female) return 'female';
  if (gender == Gender.other) return 'other';
  return '';
}

class User {
  final int id;
  final String firstname;
  final String? insertion;
  final String lastname;
  final Gender? gender;
  final DateTime? birthday;
  final String? email;
  final String? phone;
  final String? address;
  final String? postcode;
  final String? city;
  final bool? receiveNews;
  final String avatar;
  final String thanks;
  double? balance;
  final bool? minor;
  final String? role;

  User({
    required this.id,
    required this.firstname,
    required this.insertion,
    required this.lastname,
    required this.gender,
    required this.birthday,
    required this.email,
    required this.phone,
    required this.address,
    required this.postcode,
    required this.city,
    required this.receiveNews,
    required this.avatar,
    required this.thanks,
    required this.balance,
    required this.minor,
    required this.role,
  });

  String get name {
    if (insertion != null) {
      return '$firstname ${insertion!} $lastname';
    }
    return '$firstname $lastname';
  }

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      firstname: json['firstname'],
      insertion: json['insertion'],
      lastname: json['lastname'],
      gender: json['gender'] != null ? genderFromString(json['gender']) : null,
      birthday: json['birthday'] != null
          ? DateTime.parse(json['birthday'])
          : null,
      email: json['email'],
      phone: json['phone'],
      address: json['address'],
      postcode: json['postcode'],
      city: json['city'],
      receiveNews: json['receive_news'],
      avatar: json['avatar'],
      thanks: json['thanks'],
      balance: json['balance']?.toDouble(),
      minor: json['minor'],
      role: json['role'],
    );
  }
}
